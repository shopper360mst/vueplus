<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\String\Slugger\SluggerInterface;

#[AsCommand(
    name: 'app:generate-form',
    description: 'Generate a new form based on CVS, GWP, or Simple templates',
)]
class GenerateFormCommand extends Command
{
    private Filesystem $filesystem;
    private SluggerInterface $slugger;
    private string $projectDir;

    public function __construct(SluggerInterface $slugger, string $projectDir)
    {
        $this->filesystem = new Filesystem();
        $this->slugger = $slugger;
        $this->projectDir = $projectDir;
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('name', InputArgument::REQUIRED, 'Form name (e.g., "Contact Form", "Registration Form")')
            ->addArgument('template', InputArgument::REQUIRED, 'Template type: cvs, gwp, or simple')
            ->addOption('namespace', null, InputOption::VALUE_OPTIONAL, 'PHP namespace for the service', 'App\\Service')
            ->addOption('form-code', null, InputOption::VALUE_OPTIONAL, 'Form code identifier (e.g., CONTACT, REGISTRATION)', null)
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Show what would be generated without creating files')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        
        $formName = $input->getArgument('name');
        $template = strtolower($input->getArgument('template'));
        $namespace = $input->getOption('namespace');
        $formCode = $input->getOption('form-code');
        $dryRun = $input->getOption('dry-run');

        // Validate template type
        $validTemplates = ['cvs', 'gwp', 'simple'];
        if (!in_array($template, $validTemplates)) {
            $io->error(sprintf('Invalid template "%s". Valid templates are: %s', $template, implode(', ', $validTemplates)));
            return Command::FAILURE;
        }

        // Generate file names and paths
        $sluggedName = $this->slugger->slug($formName)->toString();
        $className = $this->toCamelCase($sluggedName) . 'FormStructureService';
        $formId = $sluggedName; // Keep hyphens for form ID
        $alpineComponentName = 'form' . $this->toCamelCase($sluggedName) . 'Alpine';
        
        if (!$formCode) {
            $formCode = strtoupper(str_replace('-', '_', $sluggedName));
        }

        // Define specific directories for each file type
        $serviceDir = $this->projectDir . '/src/Service';
        $alpineDir = $this->projectDir . '/assets/alpines';
        $twigDir = $this->projectDir . '/templates/form';

        $io->title("Generating Form: $formName");
        $io->text([
            "Template: $template",
            "Class Name: $className",
            "Form ID: $formId",
            "Form Code: $formCode",
            "Alpine Component: $alpineComponentName",
            "Service Directory: $serviceDir",
            "Alpine Directory: $alpineDir", 
            "Twig Directory: $twigDir"
        ]);

        if ($dryRun) {
            $io->warning('DRY RUN - No files will be created');
        }

        try {
            // Get template files
            $templateFiles = $this->getTemplateFiles($template);
            
            // Generate files
            $generatedFiles = [];
            
            // 1. Generate PHP Service
            $serviceContent = $this->generatePhpService($templateFiles['service'], $className, $namespace, $formCode, $formName);
            $servicePath = $serviceDir . '/' . $className . '.php';
            $generatedFiles['PHP Service'] = $servicePath;
            
            // 2. Generate Twig Template
            $twigContent = $this->generateTwigTemplate($templateFiles['twig'], $formId, $formName, $alpineComponentName);
            $twigPath = $twigDir . '/form.' . $formId . '.server-rendered.html.twig';
            $generatedFiles['Twig Template'] = $twigPath;
            
            // 3. Generate Alpine.js Component
            $alpineContent = $this->generateAlpineComponent($templateFiles['alpine'], $alpineComponentName, $formId, $formCode);
            $alpinePath = $alpineDir . '/form.' . $formId . '.server-rendered.alpine.js';
            $generatedFiles['Alpine.js Component'] = $alpinePath;
            
            // 4. Apply template-specific customizations
            $twigContent = $this->applyTemplateSpecificCustomizations($twigContent, $template, $formId, $formName);
            $alpineContent = $this->applyTemplateSpecificCustomizations($alpineContent, $template, $formId, $formCode);

            if (!$dryRun) {
                // Create directories if they don't exist
                $this->filesystem->mkdir($serviceDir);
                $this->filesystem->mkdir($alpineDir);
                $this->filesystem->mkdir($twigDir);
                
                // Write files to their respective directories
                $this->filesystem->dumpFile($generatedFiles['PHP Service'], $serviceContent);
                $this->filesystem->dumpFile($generatedFiles['Twig Template'], $twigContent);
                $this->filesystem->dumpFile($generatedFiles['Alpine.js Component'], $alpineContent);
            }

            // Show generated files
            $io->success('Form generated successfully!');
            $io->section('Generated Files:');
            foreach ($generatedFiles as $type => $path) {
                $io->text("$type: $path");
            }

            // Show next steps
            $this->showNextSteps($io, $className, $formId, $sluggedName, $template);

        } catch (\Exception $e) {
            $io->error('Failed to generate form: ' . $e->getMessage());
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }

    private function getTemplateFiles(string $template): array
    {
        $templateDir = $this->projectDir . '/form-template';
        
        switch ($template) {
            case 'cvs':
                return [
                    'service' => $this->projectDir . '/src/Service/CvsFormStructureService.php',
                    'twig' => $this->projectDir . '/templates/form/form.cvs.server-rendered.html.twig',
                    'alpine' => $this->projectDir . '/assets/alpines/form.cvs.server-rendered.alpine.js'
                ];
            case 'gwp':
                return [
                    'service' => $templateDir . '/examples/gwp-based-form/GwpBasedFormStructureService.php',
                    'twig' => $templateDir . '/examples/gwp-based-form/form.gwp-based.server-rendered.html.twig',
                    'alpine' => $templateDir . '/examples/gwp-based-form/form.gwp-based.server-rendered.alpine.js'
                ];
            case 'simple':
                return [
                    'service' => $templateDir . '/templates/ExampleFormStructureService.php',
                    'twig' => $templateDir . '/templates/form.example.server-rendered.html.twig',
                    'alpine' => $templateDir . '/templates/form.example.server-rendered.alpine.js'
                ];
            default:
                throw new \InvalidArgumentException("Unknown template: $template");
        }
    }

    private function generatePhpService(string $templatePath, string $className, string $namespace, string $formCode, string $formName): string
    {
        $content = file_get_contents($templatePath);
        
        // Replace class name and namespace
        $content = preg_replace('/class \w+FormStructureService/', "class $className", $content);
        $content = preg_replace('/namespace [^;]+;/', "namespace $namespace;", $content);
        
        // Replace form code (only in the form_code field, not in NRIC options)
        $content = preg_replace("/('name' => 'form_code',.*?'value' => ')[^']*(')/s", "\$1$formCode\$2", $content);
        
        // Update comments
        $content = preg_replace('/\* [^*]+ Form Structure Service/', "* $formName Form Structure Service", $content);
        
        return $content;
    }

    private function generateTwigTemplate(string $templatePath, string $formId, string $formName, string $alpineComponentName): string
    {
        $content = file_get_contents($templatePath);
        
        // Create a safe form variable name (replace hyphens with underscores for Twig variables)
        $formVariableName = str_replace('-', '_', strtolower($formId));
        
        // Replace form ID in all possible formats
        $formIdReplacements = ['cvs', 'gwp', 'contact', 'gwp-based', 'example'];
        foreach ($formIdReplacements as $oldId) {
            $content = str_replace("id=\"$oldId\"", "id=\"$formId\"", $content);
        }
        
        // Replace Alpine component data attributes
        $alpineReplacements = ['formCVSAlpine', 'formCvsAlpine', 'formGWPAlpine', 'formContactAlpine', 'formGwpBasedAlpine', 'formExampleAlpine'];
        foreach ($alpineReplacements as $oldAlpine) {
            $content = str_replace("x-data=\"$oldAlpine\"", "x-data=\"$alpineComponentName\"", $content);
        }
        
        // Replace form structure variables - be more comprehensive to catch all instances
        $content = preg_replace('/\b(cvs|gwp|contact|gwp_based|example)_form_structure\b/', "{$formVariableName}_form_structure", $content);
        
        // Replace window variables
        $content = preg_replace('/window\.(cvs|gwp|contact|gwp_based|example)_form_structure/', "window.{$formVariableName}_form_structure", $content);
        
        // Replace Alpine events - be more specific
        $content = preg_replace('/@alpine:(cvs|gwp|contact|gwp-based|example)\.window/', "@alpine:$formId.window", $content);
        
        // Update main form title only - be very specific to avoid delivery section
        $content = preg_replace('/<h1[^>]*class="text-2xl text-white font-bold mb-5 text-center"[^>]*>.*?<\/h1>/', "<h1 class=\"text-2xl text-white font-bold mb-5 text-center\">Submission Form</h1>", $content);
        
        // Also handle the header title (different pattern)
        $content = preg_replace('/<h1[^>]*class="[^"]*text-lg[^"]*"[^>]*>.*?<\/h1>/', "<h1 class=\"text-lg md:text-xl text-white font-bold text-center py-3 w-full\" x-text=\"title\"></h1>", $content);
        
        // Update comments
        $content = preg_replace('/\{#[^#]*Form[^#]*Template[^#]*#\}/', "{# $formName - Generated Form Template #}", $content, 1);
        
        // Fix debug comments
        $content = preg_replace('/<!-- Debug: Form structure loaded with \{\{[^}]+\}\} form fields -->/', "<!-- Debug: Form structure loaded with {{ {$formVariableName}_form_structure.form_group|length }} form fields -->", $content);
        
        return $content;
    }

    private function generateAlpineComponent(string $templatePath, string $alpineComponentName, string $formId, string $formCode): string
    {
        $content = file_get_contents($templatePath);
        
        // Replace Alpine component name - handle different naming patterns
        $alpinePatterns = [
            "/Alpine\.data\('formCVSAlpine',/",
            "/Alpine\.data\('formCvsAlpine',/",
            "/Alpine\.data\('formGWPAlpine',/", 
            "/Alpine\.data\('formContactAlpine',/",
            "/Alpine\.data\('formGwpBasedAlpine',/",
            "/Alpine\.data\('formExampleAlpine',/"
        ];
        
        foreach ($alpinePatterns as $pattern) {
            $content = preg_replace($pattern, "Alpine.data('$alpineComponentName',", $content);
        }
        
        // Replace form code in all locations:
        // 1. In formData object
        $content = preg_replace("/'form_code':\s*'[^']*'/", "'form_code': '$formCode'", $content);
        
        // 2. In handleFormSubmit payload (fallback value)
        $content = preg_replace("/form_code:\s*self\.formData\.form_code\s*\|\|\s*'[^']*'/", "form_code: self.formData.form_code || '$formCode'", $content);
        
        // 3. In resetForm method
        $content = preg_replace("/(form_code:\s*')[^']*(')/", "\$1$formCode\$2", $content);
        
        // Replace form selectors in JavaScript - handle different form IDs
        $formSelectors = ['cvs', 'gwp', 'contact', 'gwp-based', 'example'];
        foreach ($formSelectors as $oldSelector) {
            $content = str_replace("getElementById('$oldSelector')", "getElementById('$formId')", $content);
            $content = str_replace("querySelector('#$oldSelector')", "querySelector('#$formId')", $content);
        }
        
        // Replace URL hash patterns for form triggering
        $hashPatterns = ['#CONTACT', '#CVS', '#GWP', '#EXAMPLE'];
        foreach ($hashPatterns as $oldHash) {
            $content = str_replace("url.includes('$oldHash')", "url.includes('#" . strtoupper($formCode) . "')", $content);
        }
        
        // Update comments
        $content = preg_replace('/\*\s*[^*\n]+\s*Alpine\.js Component/', "* $formId Form Alpine.js Component", $content);
        
        // Update console.log messages
        $content = preg_replace("/console\.log\('launching \w+ form'\)/", "console.log('launching $formId form')", $content);
        
        return $content;
    }

    private function applyTemplateSpecificCustomizations(string $content, string $template, string $formId, string $identifier): string
    {
        switch ($template) {
            case 'gwp':
                return $this->applyGwpCustomizations($content, $formId, $identifier);
            case 'cvs':
                return $this->applyCvsCustomizations($content, $formId, $identifier);
            case 'simple':
                return $this->applySimpleCustomizations($content, $formId, $identifier);
            default:
                return $content;
        }
    }
    
    private function applyGwpCustomizations(string $content, string $formId, string $identifier): string
    {
        // For Twig templates
        if (strpos($content, 'x-data=') !== false) {
            // Ensure GWP-specific structure variables are preserved
            $content = str_replace('gwp_form_structure', str_replace('-', '_', strtolower($formId)) . '_form_structure', $content);
            
            // Keep the main form title as the form name, don't replace it with "Delivery Details"
            // The "Delivery Details" title already exists in the delivery section of the template
        }
        
        // For Alpine.js components
        if (strpos($content, 'Alpine.data') !== false) {
            // Ensure GWP uses correct submission endpoint
            $content = str_replace('/endpoint/submit-contact', '/endpoint/submit', $content);
            
            // Preserve GWP-specific hash patterns
            $gwpHashes = ['#MONT', '#ECOMM', '#SHM', '#S99'];
            foreach ($gwpHashes as $hash) {
                if (strpos($content, $hash) === false) {
                    // Add GWP hash patterns if they don't exist
                    $content = str_replace(
                        "url.includes('#" . strtoupper($identifier) . "')",
                        "url.includes('#" . strtoupper($identifier) . "') || url.includes('#MONT') || url.includes('#ECOMM') || url.includes('#SHM') || url.includes('#S99')",
                        $content
                    );
                    break;
                }
            }
        }
        
        return $content;
    }
    
    private function applyCvsCustomizations(string $content, string $formId, string $identifier): string
    {
        // For Alpine.js components
        if (strpos($content, 'Alpine.data') !== false) {
            // Ensure CVS uses correct submission endpoint
            $content = str_replace('/endpoint/submit', '/endpoint/submit-contact', $content);
        }
        
        return $content;
    }
    
    private function applySimpleCustomizations(string $content, string $formId, string $identifier): string
    {
        // Simple forms might have their own customizations
        return $content;
    }

    private function toCamelCase(string $string): string
    {
        return str_replace(' ', '', ucwords(str_replace(['-', '_'], ' ', $string)));
    }

    private function showNextSteps(SymfonyStyle $io, string $className, string $formId, string $sluggedName, string $template): void
    {
        $io->section('Next Steps:');
        
        $baseSteps = [
            "1. Files have been automatically placed in their correct directories",
            "2. Register $className in config/services.yaml",
            "3. Add webpack entry for '$formId-form-server-rendered'",
            "4. Create page controller to display the form",
            "5. Create page template in templates/$sluggedName/",
            "6. Run 'npm run build' to compile assets",
            "7. Test the form functionality"
        ];
        
        // Add template-specific steps
        switch ($template) {
            case 'gwp':
                $baseSteps[] = "8. Form will submit to /endpoint/submit (GWP endpoint)";
                $baseSteps[] = "9. Test delivery address auto-population";
                $baseSteps[] = "10. Verify postcode-to-city/state mapping works";
                break;
            case 'cvs':
                $baseSteps[] = "8. Form will submit to /endpoint/submit-contact (CVS endpoint)";
                $baseSteps[] = "9. Test contact form validation";
                break;
            case 'simple':
                $baseSteps[] = "8. Configure submission endpoint as needed";
                $baseSteps[] = "9. Customize form fields in the service class";
                break;
        }
        
        $io->listing($baseSteps);

        // Template-specific notes
        $notes = [
            'All generated files are ready to use with your existing server-rendered form system.',
            "The form follows the same patterns as your existing $template forms."
        ];
        
        switch ($template) {
            case 'gwp':
                $notes[] = 'GWP forms include delivery address functionality and postcode integration.';
                $notes[] = 'Form can be triggered with hash patterns: #MONT, #ECOMM, #SHM, #S99';
                break;
            case 'cvs':
                $notes[] = 'CVS forms are optimized for contact/customer service interactions.';
                break;
            case 'simple':
                $notes[] = 'Simple forms provide a basic template you can customize as needed.';
                break;
        }
        
        $notes[] = 'No custom submission controller needed - uses existing endpoints.';
        
        $io->note($notes);
    }
}