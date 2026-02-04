<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

#[AsCommand(
    name: 'app:toggle-access-mode',
    description: 'Toggle between normal mode and admin-only mode'
)]
class ToggleAccessModeCommand extends Command
{
    private string $projectDir;
    
    public function __construct(ParameterBagInterface $params)
    {
        $this->projectDir = $params->get('kernel.project_dir');
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('mode', InputArgument::REQUIRED, 'Mode: "admin-only" or "normal"');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $mode = $input->getArgument('mode');

        if (!in_array($mode, ['admin-only', 'normal'])) {
            $io->error('Mode must be either "admin-only" or "normal"');
            return Command::FAILURE;
        }

        // Update the configuration file
        $configFile = $this->projectDir . '/config/services.yaml';
        $this->updateServicesConfig($configFile, $mode);

        // Create or update the event listener
        $this->createEventListener();

        // Register the event listener in services.yaml
        $this->registerEventListener();

        $io->success("Successfully switched to $mode mode.");
        $io->note('The system will now ' . ($mode === 'admin-only' ? 'redirect all non-admin routes to /access' : 'allow normal access to all routes'));

        return Command::SUCCESS;
    }

    private function updateServicesConfig(string $configFile, string $mode): void
    {
        // Read existing content
        $existingContent = file_get_contents($configFile);

        // Check if app.access_mode already exists
        if (strpos($existingContent, 'app.access_mode:') !== false) {
            // Replace existing app.access_mode
            $existingContent = preg_replace(
                '/app\.access_mode:\s*[\'"]?[^\'"\n]*[\'"]?/',
                "app.access_mode: '$mode'",
                $existingContent
            );
        } else {
            // Find the last parameter and add our new one after it
            $lines = explode("\n", $existingContent);
            $servicesLineIndex = -1;
            
            // Find the line with 'services:'
            for ($i = 0; $i < count($lines); $i++) {
                if (trim($lines[$i]) === 'services:') {
                    $servicesLineIndex = $i;
                    break;
                }
            }
            
            if ($servicesLineIndex > 0) {
                // Insert the new parameter before the services section
                array_splice($lines, $servicesLineIndex, 0, "    app.access_mode: '$mode'");
                $existingContent = implode("\n", $lines);
            }
        }

        file_put_contents($configFile, $existingContent);
    }

    private function createEventListener(): void
    {
        $listenerDir = $this->projectDir . '/src/EventListener';
        if (!is_dir($listenerDir)) {
            mkdir($listenerDir, 0755, true);
        }

        $listenerContent = '<?php

namespace App\EventListener;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Security\Core\Security;

class AccessModeListener
{
    private ParameterBagInterface $params;
    private ?Security $security;

    public function __construct(ParameterBagInterface $params, Security $security = null)
    {
        $this->params = $params;
        $this->security = $security;
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        $pathInfo = $request->getPathInfo();

        // Get the current access mode
        $accessMode = $this->params->get(\'app.access_mode\');

        // Only redirect if in admin-only mode
        if ($accessMode !== \'admin-only\') {
            return;
        }

        // Define admin routes that should remain accessible
        $adminRoutes = [\'/admin\', \'/access\', \'/logout\', \'/report\'];
        
        // Check if current route is an admin route
        $isAdminRoute = false;
        foreach ($adminRoutes as $adminRoute) {
            if (strpos($pathInfo, $adminRoute) === 0) {
                $isAdminRoute = true;
                break;
            }
        }

        // Skip API routes and assets
        if (strpos($pathInfo, \'/api/\') === 0 || 
            strpos($pathInfo, \'/assets/\') === 0 || 
            strpos($pathInfo, \'/bundles/\') === 0 ||
            strpos($pathInfo, \'/_\') === 0) {
            return;
        }

        // If not an admin route and not already on access page, redirect to access
        if (!$isAdminRoute && $pathInfo !== \'/access\') {
            $response = new RedirectResponse(\'/access\');
            $event->setResponse($response);
        }
    }
}';

        file_put_contents($listenerDir . '/AccessModeListener.php', $listenerContent);
    }

    private function registerEventListener(): void
    {
        $servicesFile = $this->projectDir . '/config/services.yaml';
        $content = file_get_contents($servicesFile);

        // Check if our listener is already registered
        if (strpos($content, 'App\EventListener\AccessModeListener') === false) {
            // Add the event listener configuration
            $listenerConfig = "\n    # Access Mode Event Listener\n";
            $listenerConfig .= "    App\\EventListener\\AccessModeListener:\n";
            $listenerConfig .= "        tags:\n";
            $listenerConfig .= "            - { name: kernel.event_listener, event: kernel.request, method: onKernelRequest }\n";

            $content .= $listenerConfig;
            file_put_contents($servicesFile, $content);
        }
    }
}