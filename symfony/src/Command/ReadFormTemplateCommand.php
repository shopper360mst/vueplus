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

#[AsCommand(
    name: 'app:read-form-template',
    description: 'Read and display form template information (CVS, GWP, Simple)',
)]
class ReadFormTemplateCommand extends Command
{
    private Filesystem $filesystem;
    private string $projectDir;

    public function __construct(string $projectDir)
    {
        $this->filesystem = new Filesystem();
        $this->projectDir = $projectDir;
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('template', InputArgument::OPTIONAL, 'Template type: cvs, gwp, simple, or all', 'all')
            ->addOption('show-structure', 's', InputOption::VALUE_NONE, 'Show detailed form structure')
            ->addOption('show-files', 'f', InputOption::VALUE_NONE, 'Show template file contents')
            ->addOption('export', null, InputOption::VALUE_OPTIONAL, 'Export template info to file', null)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        
        $template = strtolower($input->getArgument('template'));
        $showStructure = $input->getOption('show-structure');
        $showFiles = $input->getOption('show-files');
        $exportFile = $input->getOption('export');

        // Validate template type
        $validTemplates = ['cvs', 'gwp', 'simple', 'all'];
        if (!in_array($template, $validTemplates)) {
            $io->error(sprintf('Invalid template "%s". Valid templates are: %s', $template, implode(', ', $validTemplates)));
            return Command::FAILURE;
        }

        $io->title('Form Template Reader');

        try {
            if ($template === 'all') {
                $this->showAllTemplates($io, $showStructure, $showFiles);
            } else {
                $this->showTemplate($io, $template, $showStructure, $showFiles);
            }

            if ($exportFile) {
                $this->exportTemplateInfo($template, $exportFile, $showStructure);
                $io->success("Template information exported to: $exportFile");
            }

        } catch (\Exception $e) {
            $io->error('Failed to read template: ' . $e->getMessage());
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }

    private function showAllTemplates(SymfonyStyle $io, bool $showStructure, bool $showFiles): void
    {
        $templates = ['cvs', 'gwp', 'simple'];
        
        foreach ($templates as $template) {
            $this->showTemplate($io, $template, $showStructure, $showFiles);
            if ($template !== end($templates)) {
                $io->newLine(2);
                $io->text(str_repeat('=', 80));
                $io->newLine();
            }
        }
    }

    private function showTemplate(SymfonyStyle $io, string $template, bool $showStructure, bool $showFiles): void
    {
        $templateInfo = $this->getTemplateInfo($template);
        
        $io->section(strtoupper($template) . ' Template');
        
        // Basic info
        $io->definitionList(
            ['Description' => $templateInfo['description']],
            ['Based On' => $templateInfo['based_on']],
            ['Use Case' => $templateInfo['use_case']],
            ['Files' => count($templateInfo['files']) . ' files']
        );

        // Features
        $io->text('<info>Features:</info>');
        foreach ($templateInfo['features'] as $feature) {
            $io->text("  • $feature");
        }

        // Files
        $io->text('<info>Template Files:</info>');
        foreach ($templateInfo['files'] as $type => $path) {
            $exists = $this->filesystem->exists($path) ? '✓' : '✗';
            $io->text("  $exists $type: " . basename($path));
        }

        if ($showStructure) {
            $this->showFormStructure($io, $template);
        }

        if ($showFiles) {
            $this->showFileContents($io, $templateInfo['files']);
        }
    }

    private function getTemplateInfo(string $template): array
    {
        $templateDir = $this->projectDir . '/form-template';
        
        switch ($template) {
            case 'cvs':
                return [
                    'description' => 'CVS-based form template for receipt submission',
                    'based_on' => 'Production CVS form (form.cvs.server-rendered.html.twig)',
                    'use_case' => 'Contest entries, receipt submissions, proof of purchase forms',
                    'features' => [
                        'Full Name, Mobile (Malaysia), Email inputs',
                        'NRIC/Passport selector with dynamic masks',
                        'File upload for receipts with validation',
                        'Receipt number input with helper icon',
                        'Channel field (auto-populated)',
                        'Hidden form code field',
                        'Privacy policy and T&C checkboxes',
                        'Server-rendered components',
                        'NRIC validation using my-nric library',
                        'File upload handling',
                        'Toast notifications and error handling'
                    ],
                    'files' => [
                        'PHP Service' => $templateDir . '/examples/simple-contact-form/ContactFormStructureService.php',
                        'Twig Template' => $templateDir . '/examples/simple-contact-form/form.contact.server-rendered.html.twig',
                        'Alpine.js Component' => $templateDir . '/examples/simple-contact-form/form.contact.server-rendered.alpine.js',
                        'README' => $templateDir . '/examples/simple-contact-form/README.md'
                    ]
                ];

            case 'gwp':
                return [
                    'description' => 'GWP-based form template with delivery details',
                    'based_on' => 'Production GWP form (form.gwp.server-rendered.html.twig)',
                    'use_case' => 'Gift with purchase, product redemption, delivery forms',
                    'features' => [
                        'All CVS features plus delivery section',
                        'Recipient details (pre-filled, editable)',
                        'Address Line 1 and 2 inputs',
                        'Postcode with advanced search/select',
                        'City and State auto-populated from postcode',
                        'Malaysia and Singapore mobile support',
                        'Delivery section with special border styling',
                        'Postcode lookup API integration',
                        'Address validation',
                        'Multi-country mobile validation'
                    ],
                    'files' => [
                        'PHP Service' => $templateDir . '/examples/gwp-based-form/GwpBasedFormStructureService.php',
                        'Twig Template' => $templateDir . '/examples/gwp-based-form/form.gwp-based.server-rendered.html.twig',
                        'README' => $templateDir . '/examples/gwp-based-form/README.md'
                    ]
                ];

            case 'simple':
                return [
                    'description' => 'Generic form template with all field types',
                    'based_on' => 'Combined patterns from CVS and GWP forms',
                    'use_case' => 'Custom forms, flexible configurations, any form type',
                    'features' => [
                        'Configurable field structure',
                        'All available field types included',
                        'Flexible group configuration (form, delivery, checkbox)',
                        'Generic template for customization',
                        'All server-rendered components',
                        'Complete validation patterns',
                        'File upload support',
                        'Mobile prefix support',
                        'NRIC/Passport support',
                        'Advanced select components'
                    ],
                    'files' => [
                        'PHP Service' => $templateDir . '/templates/ExampleFormStructureService.php',
                        'Twig Template' => $templateDir . '/templates/form.example.server-rendered.html.twig',
                        'Alpine.js Component' => $templateDir . '/templates/form.example.server-rendered.alpine.js'
                    ]
                ];

            default:
                throw new \InvalidArgumentException("Unknown template: $template");
        }
    }

    private function showFormStructure(SymfonyStyle $io, string $template): void
    {
        $io->text('<info>Form Structure:</info>');
        
        try {
            $structure = $this->getFormStructureFromTemplate($template);
            
            foreach ($structure as $groupName => $fields) {
                $io->text("  <comment>$groupName:</comment> (" . count($fields) . " fields)");
                
                foreach ($fields as $field) {
                    $required = $field['required'] ? ' (required)' : '';
                    $component = $field['component'];
                    $type = isset($field['type']) ? $field['type'] : '';
                    
                    $io->text("    • {$field['label']} - $component/$type$required");
                }
                $io->newLine();
            }
            
        } catch (\Exception $e) {
            $io->warning("Could not load form structure: " . $e->getMessage());
        }
    }

    private function getFormStructureFromTemplate(string $template): array
    {
        $templateInfo = $this->getTemplateInfo($template);
        $servicePath = $templateInfo['files']['PHP Service'];
        
        if (!$this->filesystem->exists($servicePath)) {
            throw new \Exception("Service file not found: $servicePath");
        }
        
        // Read and parse the PHP service file to extract structure
        $content = file_get_contents($servicePath);
        
        // This is a simplified parser - in a real implementation you might want to
        // actually instantiate the service class and call getFormStructure()
        $structure = [];
        
        // Extract form groups using regex (simplified approach)
        if (preg_match_all("/'(\w+_group)'\s*=>\s*\[(.*?)\]/s", $content, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $groupName = $match[1];
                $groupContent = $match[2];
                
                // Extract fields from group
                if (preg_match_all("/\[\s*'index'\s*=>\s*\d+,.*?'label'\s*=>\s*'([^']*)'.*?'component'\s*=>\s*'([^']*)'.*?'type'\s*=>\s*'([^']*)'.*?'required'\s*=>\s*(true|false)/s", $groupContent, $fieldMatches, PREG_SET_ORDER)) {
                    $structure[$groupName] = [];
                    foreach ($fieldMatches as $fieldMatch) {
                        $structure[$groupName][] = [
                            'label' => $fieldMatch[1],
                            'component' => $fieldMatch[2],
                            'type' => $fieldMatch[3],
                            'required' => $fieldMatch[4] === 'true'
                        ];
                    }
                }
            }
        }
        
        return $structure;
    }

    private function showFileContents(SymfonyStyle $io, array $files): void
    {
        $io->text('<info>File Contents:</info>');
        
        foreach ($files as $type => $path) {
            if (!$this->filesystem->exists($path)) {
                $io->text("  <error>$type: File not found</error>");
                continue;
            }
            
            $io->text("  <comment>$type:</comment>");
            
            $content = file_get_contents($path);
            $lines = explode("\n", $content);
            
            // Show first 20 lines of each file
            $preview = array_slice($lines, 0, 20);
            foreach ($preview as $lineNum => $line) {
                $io->text(sprintf("    %3d: %s", $lineNum + 1, $line));
            }
            
            if (count($lines) > 20) {
                $io->text("    ... (" . (count($lines) - 20) . " more lines)");
            }
            
            $io->newLine();
        }
    }

    private function exportTemplateInfo(string $template, string $exportFile, bool $includeStructure): void
    {
        $templates = $template === 'all' ? ['cvs', 'gwp', 'simple'] : [$template];
        
        $exportData = [
            'generated_at' => date('Y-m-d H:i:s'),
            'templates' => []
        ];
        
        foreach ($templates as $tmpl) {
            $info = $this->getTemplateInfo($tmpl);
            
            $templateData = [
                'name' => strtoupper($tmpl),
                'description' => $info['description'],
                'based_on' => $info['based_on'],
                'use_case' => $info['use_case'],
                'features' => $info['features'],
                'files' => []
            ];
            
            // Add file information
            foreach ($info['files'] as $type => $path) {
                $templateData['files'][$type] = [
                    'path' => $path,
                    'exists' => $this->filesystem->exists($path),
                    'size' => $this->filesystem->exists($path) ? filesize($path) : 0
                ];
            }
            
            // Add structure if requested
            if ($includeStructure) {
                try {
                    $templateData['structure'] = $this->getFormStructureFromTemplate($tmpl);
                } catch (\Exception $e) {
                    $templateData['structure_error'] = $e->getMessage();
                }
            }
            
            $exportData['templates'][$tmpl] = $templateData;
        }
        
        // Export as JSON
        $json = json_encode($exportData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        $this->filesystem->dumpFile($exportFile, $json);
    }
}