<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use DOMDocument;
use DOMXPath;

#[AsCommand(
    name: 'app:faq-transfer',
    description: 'Parse FAQ data from Word-exported HTML and output as structured array'
)]
class FaqTransferCommand extends Command
{
    private const IMAGE_PATHS = [
        'en' => [
            'base' => 'images/FAQ/',
            'filesystem' => 'public/images/FAQ/'
        ],
        'ch' => [
            'base' => 'images/FAQCH/',
            'filesystem' => 'public/images/FAQCH/'
        ]
    ];

    protected function configure(): void
    {
        $this
            ->addArgument(
                'html-file',
                InputArgument::REQUIRED,
                'Full path to the HTML file exported from Word'
            )
            ->addArgument(
                'locale',
                InputArgument::OPTIONAL,
                'Locale code (en or ch). Default: en',
                'en'
            )
            ->addOption(
                'source-path',
                's',
                InputOption::VALUE_OPTIONAL,
                'Source directory containing the .fld folder. Default: directory of HTML file',
                null
            )
            ->addOption(
                'output-file',
                'o',
                InputOption::VALUE_OPTIONAL,
                'Save output to JSON file (if not specified, prints to console)',
                null
            )
            ->addOption(
                'format',
                'f',
                InputOption::VALUE_OPTIONAL,
                'Output format: json or text. Default: json',
                'json'
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        
        $htmlFilePath = $input->getArgument('html-file');
        $locale = $input->getArgument('locale');
        $sourcePath = $input->getOption('source-path') ?? dirname($htmlFilePath);
        $outputFile = $input->getOption('output-file');
        $format = $input->getOption('format');
        
        // Validate inputs
        if (!file_exists($htmlFilePath)) {
            $io->error("HTML file not found: {$htmlFilePath}");
            return Command::FAILURE;
        }

        if (!in_array($locale, ['en', 'ch'])) {
            $io->error("Invalid locale. Must be 'en' or 'ch'");
            return Command::FAILURE;
        }

        if (!in_array($format, ['json', 'text'])) {
            $io->error("Invalid format. Must be 'json' or 'text'");
            return Command::FAILURE;
        }

        $io->section("FAQ Parser - Locale: {$locale}");
        $io->text("Source HTML: {$htmlFilePath}");

        try {
            // Read HTML content
            $htmlContent = file_get_contents($htmlFilePath);
            
            // Handle image copying
            $io->comment('Processing images from .fld folder...');
            $htmlContent = $this->handleImages($htmlContent, basename($htmlFilePath), $sourcePath, $locale, $io);
            
            // Parse FAQ data
            $io->comment('Parsing FAQ data from HTML...');
            $faqArray = $this->createFaqArrayFromWordHtml($htmlContent);
            
            if (empty($faqArray)) {
                $io->warning('No FAQ data found in the HTML file');
                return Command::FAILURE;
            }

            $io->success("Found " . count($faqArray) . " FAQ entries");

            // Display preview
            $this->displayPreview($faqArray, $io);

            // Format and output data
            $output_data = $this->formatOutput($faqArray, $locale, $format);

            if ($outputFile) {
                file_put_contents($outputFile, $output_data);
                $io->success("Output saved to: {$outputFile}");
            } else {
                $io->writeln("\n" . str_repeat("=", 80));
                $io->writeln("PARSED FAQ DATA:");
                $io->writeln(str_repeat("=", 80) . "\n");
                $io->writeln($output_data);
            }

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $io->error('Error: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }

    private function handleImages(
        string $htmlContent,
        string $htmlFileName,
        string $sourcePath,
        string $locale,
        SymfonyStyle $io
    ): string {
        $fldFolderName = pathinfo($htmlFileName, PATHINFO_FILENAME) . '.fld';
        $sourceFldPath = $sourcePath . DIRECTORY_SEPARATOR . $fldFolderName;

        if (!is_dir($sourceFldPath)) {
            $io->warning("FLD folder not found at: {$sourceFldPath}");
            return $htmlContent;
        }

        // Get locale-specific paths
        $imagePaths = self::IMAGE_PATHS[$locale];
        $projectRoot = getcwd();
        $imageFileSystemPath = $projectRoot . DIRECTORY_SEPARATOR . $imagePaths['filesystem'];

        // Ensure destination directory exists
        if (!is_dir($imageFileSystemPath)) {
            mkdir($imageFileSystemPath, 0755, true);
        }

        // Copy images from .fld folder
        $imageFiles = scandir($sourceFldPath);
        $copiedCount = 0;

        foreach ($imageFiles as $file) {
            if ($file !== '.' && $file !== '..') {
                $source = $sourceFldPath . DIRECTORY_SEPARATOR . $file;
                $destination = $imageFileSystemPath . $file;
                
                if (is_file($source)) {
                    copy($source, $destination);
                    $copiedCount++;
                }
            }
        }

        $io->text("Copied {$copiedCount} image(s) to {$imageFileSystemPath}");

        // Rewrite image paths in HTML
        $dom = new DOMDocument();
        @$dom->loadHTML($htmlContent, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        $xpath = new DOMXPath($dom);

        $imageNodes = $xpath->query('//@src');
        $rewriteCount = 0;

        foreach ($imageNodes as $node) {
            $currentSrc = $node->nodeValue;
            if (strpos($currentSrc, $fldFolderName) !== false) {
                $fileName = basename($currentSrc);
                $node->nodeValue = '/' . $imagePaths['base'] . $fileName;
                $rewriteCount++;
            }
        }

        $htmlContent = $dom->saveHTML();
        $io->text("Rewritten {$rewriteCount} image path(s)");

        return $htmlContent;
    }

    private function createFaqArrayFromWordHtml(string $htmlContent): array
    {
        if (empty($htmlContent)) {
            return [];
        }

        $dom = new DOMDocument();
        @$dom->loadHTML($htmlContent, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        $xpath = new DOMXPath($dom);

        $faqs = [];
        $currentCategory = '';
        
        // Find the WordSection1 div, or fall back to direct body children
        $sectionDiv = $xpath->query('//div[@class="WordSection1"]');
        if ($sectionDiv->length > 0) {
            $nodes = $xpath->query('./*', $sectionDiv->item(0));
        } else {
            $nodes = $xpath->query('//body/*');
        }

        for ($i = 0; $i < $nodes->length; $i++) {
            $node = $nodes->item($i);
            $nodeText = trim($node->textContent);

            // Identify Category: span with 16pt font
            $categoryNode = $xpath->query(".//span[contains(@style, 'font-size:16.0pt')]", $node);
            if ($categoryNode->length > 0) {
                $currentCategory = trim($categoryNode->item(0)->textContent);
                continue;
            }

            // Identify Question: paragraph with MsoListParagraph class and bold numbered text
            // These are typically MsoListParagraphCxSpFirst, MsoListParagraphCxSpMiddle, or MsoListParagraphCxSpLast
            if (strtolower($node->nodeName) === 'p') {
                $class = $node->getAttribute('class');
                
                // Check if this is a list paragraph (question)
                if (strpos($class, 'MsoListParagraph') !== false) {
                    // Find the first <b> element which contains the question number and text
                    $boldNodes = $xpath->query('.//b', $node);
                    if ($boldNodes->length > 0) {
                        $boldText = trim($boldNodes->item(0)->textContent);
                        // Check if it starts with a number (question marker like "1.", "2.", etc.)
                        if (preg_match('/^\d+\./', $boldText)) {
                            // Extract full question: look for the question mark to find where it ends
                            $fullText = $nodeText;
                            
                            // Find the position of the first question mark
                            $questionMarkPos = strpos($fullText, '?');
                            if ($questionMarkPos !== false) {
                                // Question ends at the question mark
                                $questionText = $this->cleanText(substr($fullText, 0, $questionMarkPos + 1));
                                // Answer starts after the question mark
                                $answerStartText = trim(substr($fullText, $questionMarkPos + 1));
                            } else {
                                // No question mark found, use the entire bold text as question
                                $questionText = $this->cleanText($this->extractAllBoldText($boldNodes, $node));
                                $answerStartText = '';
                            }
                            
                            $answerHtml = '';
                            $images = [];
                            
                            // If there's answer text in the same paragraph after the question, add it
                            if (!empty($answerStartText)) {
                                $answerHtml .= $this->cleanHtml($answerStartText);
                            }

                            // Gather answer nodes until we hit the next question or category
                            $j = $i + 1;
                            while ($j < $nodes->length) {
                                $nextNode = $nodes->item($j);
                                $nextNodeText = trim($nextNode->textContent);

                                // Stop at next category (16pt font)
                                $isNextCategory = $xpath->query(
                                    ".//span[contains(@style, 'font-size:16.0pt')]",
                                    $nextNode
                                )->length > 0;
                                
                                // Stop at next question (MsoListParagraph with bold numbered text)
                                $isNextQuestion = false;
                                if (strtolower($nextNode->nodeName) === 'p') {
                                    $nextClass = $nextNode->getAttribute('class');
                                    if (strpos($nextClass, 'MsoListParagraph') !== false) {
                                        $nextBoldNodes = $xpath->query('.//b', $nextNode);
                                        if ($nextBoldNodes->length > 0) {
                                            $nextBoldText = trim($nextBoldNodes->item(0)->textContent);
                                            if (preg_match('/^\d+\./', $nextBoldText)) {
                                                $isNextQuestion = true;
                                            }
                                        }
                                    }
                                }

                                if ($isNextCategory || $isNextQuestion) {
                                    break;
                                }

                                // Include answer content (tables and paragraphs)
                                if (!empty($nextNodeText)) {
                                    // Extract images from this node
                                    $nodeImages = $this->extractImages($nextNode, $xpath);
                                    $images = array_merge($images, $nodeImages);
                                    
                                    // Clean and add HTML (only keep tables and important content)
                                    if ($nextNode->nodeName === 'table' || strtolower($nextNode->nodeName) === 'table') {
                                        $answerHtml .= $this->cleanHtml($nextNode->ownerDocument->saveHTML($nextNode));
                                    } else {
                                        // For non-table elements, extract text and clean
                                        $answerHtml .= $this->cleanHtml($nextNode->ownerDocument->saveHTML($nextNode));
                                    }
                                }
                                $j++;
                            }

                            // Combine consecutive <ul> lists into a single list
                            $answerHtml = preg_replace('|</ul>\s*<ul>|', '', $answerHtml);
                            
                            $faqEntry = [
                                'category' => $this->cleanText($currentCategory),
                                'question' => $questionText,
                                'answer' => trim($answerHtml),
                            ];
                            
                            // Add images if any found
                            if (!empty($images)) {
                                $faqEntry['images'] = $images;
                            }

                            $faqs[] = $faqEntry;

                            $i = $j - 1;
                        }
                    }
                }
            }
        }

        return $faqs;
    }

    private function cleanText(string $text): string
    {
        // Remove carriage returns and newlines
        $text = str_replace(["\r\n", "\r", "\n"], ' ', $text);
        // Remove extra spaces
        $text = preg_replace('/\s+/', ' ', $text);
        return trim($text);
    }

    private function extractAllBoldText($boldNodes, $node): string
    {
        $allBoldText = '';
        for ($i = 0; $i < $boldNodes->length; $i++) {
            $allBoldText .= $boldNodes->item($i)->textContent;
        }
        return $allBoldText;
    }

    private function cleanHtml(string $html): string
    {
        // Remove XML declarations
        $html = preg_replace('/<\?xml[^?]*\?>/', '', $html);
        
        // Remove HTML document wrapper tags that might be added by DOMDocument
        $html = preg_replace('/<html>|<\/html>|<body>|<\/body>|<head>|<\/head>|<!DOCTYPE[^>]*>/i', '', $html);
        
        // Remove <p> tags but preserve their content
        $html = preg_replace('/<p[^>]*>/', '', $html);
        $html = preg_replace('/<\/p>/', '', $html);
        
        // Remove <span> tags but preserve their content
        $html = preg_replace('/<span[^>]*>/', '', $html);
        $html = preg_replace('/<\/span>/', '', $html);
        
        // PRESERVE <ul>, <li>, <ol> tags - important for list structure
        // They should remain in the output
        
        // Clean up newlines and carriage returns inside the HTML content
        // But preserve them inside <li> tags for readability
        $html = preg_replace('/\s*\r\n\s*/', ' ', $html);
        
        // Collapse multiple spaces, but not inside tags
        $html = preg_replace('/  +/', ' ', $html);
        
        // Convert bullet point text to proper <ul><li> structure
        $html = $this->convertBulletsToList($html);
        
        return trim($html);
    }

    private function convertBulletsToList(string $text): string
    {
        // Handle UTF-8 encoding issues with bullet character
        // The bullet can be "·" or "Â·" (UTF-8 encoded version)
        
        // Normalize the bullet character
        $text = str_replace("Â·", "·", $text);
        
        // Check if there's a bullet point at all - single bullets should become lists too
        if (strpos($text, '·') === false) {
            return $text; // No bullets, return as-is
        }
        
        // Split by bullet character to get list items
        $items = explode('·', $text);
        
        // First item before the first bullet might be prefix text
        $prefix = trim(array_shift($items));
        
        // Filter empty items and trim whitespace
        $items = array_map('trim', array_filter($items));
        
        if (empty($items)) {
            return $prefix; // No items found, return prefix
        }
        
        // If only one item and no prefix, this might be a single bullet point
        // Still convert it to a list for consistency
        
        // Build HTML list
        $listHtml = '<ul>';
        foreach ($items as $item) {
            $listHtml .= '<li>' . htmlspecialchars($item, ENT_QUOTES, 'UTF-8') . '</li>';
        }
        $listHtml .= '</ul>';
        
        // Return prefix text (if any) followed by the list
        return $prefix ? $prefix . ' ' . $listHtml : $listHtml;
    }

    private function extractImages(\DOMNode $node, DOMXPath $xpath): array
    {
        $images = [];
        $imgNodes = $xpath->query('.//img', $node);
        
        foreach ($imgNodes as $imgNode) {
            $src = $imgNode->getAttribute('src');
            $alt = $imgNode->getAttribute('alt');
            
            if (!empty($src)) {
                $images[] = [
                    'url' => $src,
                    'alt' => $alt ?? '',
                ];
            }
        }
        
        return $images;
    }

    private function displayPreview(array $faqArray, SymfonyStyle $io): void
    {
        $io->section('Preview - First 3 FAQs');
        
        foreach (array_slice($faqArray, 0, 3) as $index => $faq) {
            $answerPreview = substr(strip_tags($faq['answer']), 0, 80);
            $io->writeln(sprintf(
                "\n<fg=cyan>FAQ #%d</> - <fg=yellow>%s</>\n<fg=green>Q:</> %s\n<fg=blue>A:</> %s...",
                $index + 1,
                $faq['category'],
                substr($faq['question'], 0, 60),
                $answerPreview
            ));
        }
        $io->text("");
    }

    private function formatOutput(array $faqArray, string $locale, string $format): string
    {
        if ($format === 'json') {
            return json_encode([
                'locale' => $locale,
                'total_count' => count($faqArray),
                'faqs' => $faqArray
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        }

        // Text format
        $output = "LOCALE: {$locale}\n";
        $output .= "TOTAL COUNT: " . count($faqArray) . "\n";
        $output .= str_repeat("=", 80) . "\n\n";

        foreach ($faqArray as $index => $faq) {
            $output .= "FAQ #" . ($index + 1) . "\n";
            $output .= "CATEGORY: " . $faq['category'] . "\n";
            $output .= "QUESTION: " . $faq['question'] . "\n";
            $output .= "ANSWER:\n" . strip_tags($faq['answer']) . "\n";
            $output .= str_repeat("-", 80) . "\n\n";
        }

        return $output;
    }
}