<?php

namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'app:run-command')]
class RunAppCommandCommand extends Command
{
    protected function configure(): void
    {
        $this->setDescription('Lists all app: commands and allows you to select one to run');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        
        // Get all available commands
        $application = $this->getApplication();
        $commands = $application->all();
        
        // Filter commands that start with "app:"
        $appCommands = [];
        foreach ($commands as $name => $command) {
            if (strpos($name, 'app:') === 0 && $name !== 'app:run-command') {
                $appCommands[$name] = $command->getDescription();
            }
        }
        
        if (empty($appCommands)) {
            $io->error('No app: commands found.');
            return Command::FAILURE;
        }
        
        // Display commands with numbers
        $io->title('Available app: commands');
        
        $i = 1;
        $commandNames = array_keys($appCommands);
        $io->newLine();
        foreach ($commandNames as $commandName) {
            $description = $appCommands[$commandName] ?: 'No description';
            $io->writeln(sprintf("  <bg=blue;fg=white> %d </> <options=bold>%s</> - %s", $i++, $commandName, $description));
            $io->writeln(''); // Add an empty line between each command
        }
        $io->newLine();
        
        // Ask for selection
        $io->newLine();
        $io->block('Enter the number of the command you want to run (or 0 to exit)', null, 'fg=black;bg=yellow', ' ', true);
        $selection = $io->ask('Command number', '0');
        $io->newLine();
        
        // Validate input
        if (!is_numeric($selection) || (int)$selection < 0 || (int)$selection > count($appCommands)) {
            $io->error('Invalid selection. Please choose a number between 1 and ' . count($appCommands));
            return Command::FAILURE;
        }
        
        if ((int)$selection === 0) {
            $io->info('Exiting.');
            return Command::SUCCESS;
        }
        
        // Get selected command
        $selectedCommandName = $commandNames[$selection - 1];
        $selectedCommand = $application->find($selectedCommandName);
        
        $io->newLine();
        $io->section("Running command");
        $io->block($selectedCommandName, null, 'fg=black;bg=magenta', ' ', true);
        $io->newLine();
        
        // Display command help information
        $io->writeln('<fg=bright-yellow;options=bold>Command description:</> ' . $selectedCommand->getDescription());
        
        // Show full help text option
        if ($io->confirm('Would you like to see the full help text for this command?', false)) {
            $io->section('Command Help');
            $helpCommand = $application->find('help');
            $helpArgs = [
                'command_name' => $selectedCommandName
            ];
            $helpInput = new ArrayInput($helpArgs);
            $helpCommand->run($helpInput, $output);
            $io->newLine();
        }
        
        // Get command definition to show available arguments and options
        $definition = $selectedCommand->getDefinition();
        
        // Display arguments
        $arguments = $definition->getArguments();
        if (count($arguments) > 0) {
            $io->newLine();
            $io->section('Available arguments');
            $io->newLine();
            foreach ($arguments as $argument) {
                $description = $argument->getDescription() ?: 'No description';
                $default = $argument->getDefault();
                $defaultText = $default ? " [default: " . (is_array($default) ? json_encode($default) : $default) . "]" : '';
                $required = $argument->isRequired() ? ' <fg=red;options=bold>(REQUIRED)</>' : '';
                $io->writeln(sprintf("  <fg=green;options=bold>%s</fg=green;options=bold>%s%s\n     %s", 
                    $argument->getName(),
                    $required,
                    $defaultText,
                    $description
                ));
                $io->newLine();
            }
        }
        
        // Display options
        $options = $definition->getOptions();
        if (count($options) > 0) {
            $io->newLine();
            $io->section('Available options');
            $io->newLine();
            foreach ($options as $option) {
                $description = $option->getDescription() ?: 'No description';
                $default = $option->getDefault();
                $defaultText = $default !== null ? " [default: " . (is_array($default) ? json_encode($default) : $default) . "]" : '';
                $shortcut = $option->getShortcut() ? sprintf('-%s, ', $option->getShortcut()) : '    ';
                $io->writeln(sprintf("  <fg=cyan;options=bold>%s--%s</fg=cyan;options=bold>%s\n     %s", 
                    $shortcut,
                    $option->getName(),
                    $defaultText,
                    $description
                ));
                $io->newLine();
            }
        }
        
        // Ask if the command should be interactive
        $io->newLine();
        $io->section('Command execution options');
        $io->newLine();
        $isInteractive = $io->confirm("Do you want to run this command in interactive mode?", true);
        
        // Ask for additional arguments or options
        $io->newLine();
        $io->block('Enter any additional arguments or options based on the information above', null, 'fg=black;bg=green', ' ', true);
        $io->note('Format: "--option=value arg1 arg2"');
        $additionalArgs = $io->ask('Additional arguments/options', '');
        $io->newLine();
        
        // Prepare command input
        $commandArgs = ['command' => $selectedCommandName];
        
        // Parse and add additional arguments if provided
        if (!empty($additionalArgs)) {
            // Get the argument names from the command definition
            $argumentNames = array_keys($definition->getArguments());
            // Remove 'command' from the list if it exists
            $argumentNames = array_filter($argumentNames, function($name) {
                return $name !== 'command';
            });
            
            $argParts = explode(' ', $additionalArgs);
            $argIndex = 0; // Track which positional argument we're on
            
            foreach ($argParts as $part) {
                if (strpos($part, '--') === 0) {
                    // Handle options (--option=value or --option)
                    $optionParts = explode('=', substr($part, 2), 2);
                    $optionName = $optionParts[0];
                    $optionValue = isset($optionParts[1]) ? $optionParts[1] : true;
                    $commandArgs['--' . $optionName] = $optionValue;
                } elseif (strpos($part, '-') === 0) {
                    // Handle short options (-o)
                    $commandArgs[substr($part, 0, 2)] = true;
                } else {
                    // Handle positional arguments
                    if (isset($argumentNames[$argIndex])) {
                        // If we have a name for this position, use it
                        $commandArgs[$argumentNames[$argIndex]] = $part;
                        $argIndex++;
                    } else {
                        // Fallback for extra arguments
                        $commandArgs[] = $part;
                    }
                }
            }
            
            // Debug output to show what arguments are being passed
            $io->comment('Passing arguments: ' . json_encode($commandArgs));
        }
        
        // Execute the selected command
        $commandInput = new ArrayInput($commandArgs);
        
        // Set interactive mode based on user choice
        $commandInput->setInteractive($isInteractive);
        
        try {
            // Run the command and capture its return code
            $io->comment('Executing command...');
            $returnCode = $selectedCommand->run($commandInput, $output);
            $io->newLine();
            $io->success("Command $selectedCommandName completed with return code: $returnCode");
            return $returnCode;
        } catch (\Exception $e) {
            $io->error("Error executing command: " . $e->getMessage());
            return Command::FAILURE;
        }
    }
}