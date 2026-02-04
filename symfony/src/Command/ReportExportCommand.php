<?php

namespace App\Command;

use App\Entity\ReportEntry;
use App\Entity\ReportByState;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(
    name: 'app:report-export',
    description: 'Export ReportEntry and ReportByState data to Excel using template format'
)]
class ReportExportCommand extends Command
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('week_number', InputArgument::REQUIRED, 'Week number to export')
            ->addOption('template', 't', InputOption::VALUE_OPTIONAL, 'Path to Excel template file', null)
            ->addOption('output-dir', 'o', InputOption::VALUE_OPTIONAL, 'Output directory for exported files', null)
            ->addOption('analyze', 'a', InputOption::VALUE_NONE, 'Analyze template structure without exporting');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        
        $weekNumber = (int) $input->getArgument('week_number');
        $templatePath = $input->getOption('template') ?: __DIR__ . '/excel/export_template.xlsx';
        $outputDir = $input->getOption('output-dir') ?: __DIR__ . '/excel/exports';
        $analyzeOnly = $input->getOption('analyze');

        if ($analyzeOnly) {
            $io->title('Template Structure Analysis');
            $io->info("Analyzing template: {$templatePath}");
            
            // Validate template file exists
            if (!file_exists($templatePath)) {
                $io->error("Template file not found: {$templatePath}");
                return Command::FAILURE;
            }
            
            // Analyze template structure
            $this->analyzeTemplateStructure($templatePath, $io);
            
            // Show specific data insertion recommendations
            $this->showDataInsertionRecommendations($templatePath, $io);
            
            // Detailed analysis of Summary Contest sheet
            $this->analyzeSummaryContestSheet($templatePath, $io);
            
            return Command::SUCCESS;
        }

        $io->title('Report Export Command');
        $io->info("Exporting data for week: {$weekNumber}");
        $io->info("Using template: {$templatePath}");
        $io->info("Output directory: {$outputDir}");

        try {
            // Validate template file exists
            if (!file_exists($templatePath)) {
                $io->error("Template file not found: {$templatePath}");
                return Command::FAILURE;
            }

            // Ensure output directory exists
            if (!is_dir($outputDir)) {
                if (!mkdir($outputDir, 0755, true)) {
                    $io->error("Could not create output directory: {$outputDir}");
                    return Command::FAILURE;
                }
                $io->info("Created output directory: {$outputDir}");
            }

            // Fetch data from database
            $io->section('Fetching data from database...');
            
            $reportEntry = $this->getReportEntryData($weekNumber);
            $reportByStateData = $this->getReportByStateData($weekNumber);

            if (!$reportEntry) {
                $io->warning("No ReportEntry data found for week {$weekNumber}");
            } else {
                $io->success("Found ReportEntry data for week {$weekNumber}");
            }

            $io->info("Found " . count($reportByStateData) . " ReportByState records for week {$weekNumber}");

            // Load template and create Excel file
            $io->section('Processing Excel template...');
            
            $excelFile = $this->createExcelFromTemplate($templatePath, $reportEntry, $reportByStateData, $weekNumber, $io);
            
            // Save the file
            $timestamp = date('Y-m-d_H-i-s');
            $filename = "report_export_week_{$weekNumber}_{$timestamp}.xlsx";
            $filepath = $outputDir . '/' . $filename;

            $writer = new Xlsx($excelFile);
            $writer->save($filepath);

            // Clean up memory
            $excelFile->disconnectWorksheets();
            unset($excelFile, $writer);
            gc_collect_cycles();

            $io->success("Excel file exported successfully!");
            $io->info("File saved as: {$filepath}");
            $io->info("File size: " . number_format(filesize($filepath) / 1024, 2) . " KB");

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $io->error("Error during export: " . $e->getMessage());
            $io->note("Stack trace: " . $e->getTraceAsString());
            return Command::FAILURE;
        }
    }

    /**
     * Get ReportEntry data for the specified week
     */
    private function getReportEntryData(int $weekNumber): ?ReportEntry
    {
        return $this->entityManager
            ->getRepository(ReportEntry::class)
            ->findOneBy(['week_number' => $weekNumber]);
    }

    /**
     * Get ReportByState data for the specified week
     */
    private function getReportByStateData(int $weekNumber): array
    {
        return $this->entityManager
            ->getRepository(ReportByState::class)
            ->findBy(['week_number' => $weekNumber]);
    }

    /**
     * Create Excel file from template, preserving format but removing formulas
     */
    private function createExcelFromTemplate(
        string $templatePath, 
        ?ReportEntry $reportEntry, 
        array $reportByStateData, 
        int $weekNumber, 
        SymfonyStyle $io
    ): \PhpOffice\PhpSpreadsheet\Spreadsheet {
        
        $io->info("Loading template file...");
        
        // Set memory limit for large Excel files
        $originalMemoryLimit = ini_get('memory_limit');
        ini_set('memory_limit', '2048M');
        
        try {
            // Configure PhpSpreadsheet for memory efficiency
            \PhpOffice\PhpSpreadsheet\Settings::setCache(
                new \PhpOffice\PhpSpreadsheet\Collection\Memory\SimpleCache1()
            );
        } catch (\Exception $e) {
            $io->warning("Could not set memory cache, continuing with default settings");
        }

        // Load the template
        $spreadsheet = IOFactory::load($templatePath);
        
        $io->info("Template loaded successfully");
        $io->info("Found " . $spreadsheet->getSheetCount() . " worksheets");

        // Process each worksheet
        foreach ($spreadsheet->getAllSheets() as $worksheet) {
            $sheetName = $worksheet->getTitle();
            $io->info("Processing worksheet: {$sheetName}");
            
            // Set this worksheet as active
            $spreadsheet->setActiveSheetIndex($spreadsheet->getIndex($worksheet));
            
            // Remove formulas and keep only values and formatting
            $this->removeFormulasKeepFormat($worksheet, $io);
            
            // Populate data based on sheet name
            if (stripos($sheetName, 'summary') !== false && stripos($sheetName, 'contest') !== false) {
                // Populate Summary Contest sheet with all channels data
                $this->populateSummaryContestSheet($worksheet, $reportEntry, $reportByStateData, $weekNumber, $io);
            } elseif ($this->isChannelSheet($sheetName)) {
                // Populate channel-specific sheet using ReportEntry data
                $channelName = $this->extractChannelFromSheetName($sheetName);
                $this->populateChannelSheet($worksheet, [], $channelName, $weekNumber, $io);
                
                // Also populate demographics section for this channel
                $this->populateDemographicsSection($worksheet, $reportEntry, $channelName, $weekNumber, $io);
                
                // Also populate rejection reasons section for this channel
                $this->populateRejectionReasonsSection($worksheet, $reportEntry, $channelName, $weekNumber, $io);
            } elseif (stripos($sheetName, 'data') !== false && stripos($sheetName, 'pivot') === false) {
                // Populate main data sheet (but skip "data for pivot")
                $this->populateDataSheet($worksheet, $reportEntry, $reportByStateData, $weekNumber, $io);
            } elseif (stripos($sheetName, 'state') !== false) {
                // Skip state sheets for now - focusing on channel sheets  
                $io->text("  ⏭️  Skipping state sheet: {$sheetName}");
            } else {
                // Skip other sheets
                $io->text("  ⏭️  Skipping sheet: {$sheetName}");
            }
        }

        // Restore memory limit
        ini_set('memory_limit', $originalMemoryLimit);
        
        return $spreadsheet;
    }

    /**
     * Analyze template structure to show available data insertion points
     */
    private function analyzeTemplateStructure(string $templatePath, SymfonyStyle $io): void
    {
        $io->title('Template Structure Analysis');
        
        try {
            // Load the template
            $spreadsheet = IOFactory::load($templatePath);
            
            foreach ($spreadsheet->getAllSheets() as $sheetIndex => $worksheet) {
                $sheetName = $worksheet->getTitle();
                $io->section("Sheet: {$sheetName}");
                
                // Get sheet dimensions
                $highestRow = $worksheet->getHighestRow();
                $highestColumn = $worksheet->getHighestColumn();
                
                $io->info("Dimensions: A1 to {$highestColumn}{$highestRow}");
                
                // Analyze first 20 rows to find headers and structure
                $headers = [];
                $dataRows = [];
                
                for ($row = 1; $row <= min(20, $highestRow); $row++) {
                    $rowData = [];
                    $hasData = false;
                    
                    for ($col = 'A'; $col <= min('Z', $highestColumn); $col++) {
                        $cell = $worksheet->getCell($col . $row);
                        $value = $cell->getCalculatedValue();
                        
                        if (!empty($value)) {
                            $hasData = true;
                            $rowData[$col] = $value;
                        }
                    }
                    
                    if ($hasData) {
                        if ($row <= 5) { // Likely headers in first 5 rows
                            $headers[$row] = $rowData;
                        } else {
                            $dataRows[$row] = $rowData;
                        }
                    }
                }
                
                // Display headers
                if (!empty($headers)) {
                    $io->text("📋 <comment>Headers/Labels found:</comment>");
                    foreach ($headers as $rowNum => $rowData) {
                        $io->text("  Row {$rowNum}: " . json_encode($rowData, JSON_UNESCAPED_UNICODE));
                    }
                }
                
                // Display sample data rows
                if (!empty($dataRows)) {
                    $io->text("📊 <comment>Sample data rows:</comment>");
                    $count = 0;
                    foreach ($dataRows as $rowNum => $rowData) {
                        if ($count >= 3) break; // Show only first 3 data rows
                        $io->text("  Row {$rowNum}: " . json_encode($rowData, JSON_UNESCAPED_UNICODE));
                        $count++;
                    }
                }
                
                // Look for empty areas that might be for data insertion
                $this->findEmptyDataAreas($worksheet, $io);
                
                $io->newLine();
            }
            
        } catch (\Exception $e) {
            $io->error("Error analyzing template: " . $e->getMessage());
        }
    }
    
    /**
     * Find empty areas in worksheet that might be for data insertion
     */
    private function findEmptyDataAreas(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $worksheet, SymfonyStyle $io): void
    {
        $io->text("🎯 <comment>Potential data insertion areas:</comment>");
        
        $highestRow = min($worksheet->getHighestRow(), 50); // Check first 50 rows
        $highestColumn = min($worksheet->getHighestColumn(), 'Z'); // Check up to column Z
        
        $emptyAreas = [];
        $currentEmptyArea = null;
        
        for ($row = 1; $row <= $highestRow; $row++) {
            $rowHasData = false;
            $emptyColumns = [];
            
            for ($col = 'A'; $col <= $highestColumn; $col++) {
                $cell = $worksheet->getCell($col . $row);
                $value = trim($cell->getCalculatedValue());
                
                if (empty($value)) {
                    $emptyColumns[] = $col;
                } else {
                    $rowHasData = true;
                }
            }
            
            // If row has some data but also empty columns, it might be a data insertion area
            if ($rowHasData && count($emptyColumns) > 2) {
                $emptyAreas[] = "Row {$row}: Empty columns " . implode(', ', array_slice($emptyColumns, 0, 5)) . (count($emptyColumns) > 5 ? '...' : '');
            }
            
            // If entire row is empty and previous row had data, it might be for new data
            if (!$rowHasData && $row > 1) {
                $prevRowHasData = false;
                for ($col = 'A'; $col <= $highestColumn; $col++) {
                    if (!empty(trim($worksheet->getCell($col . ($row - 1))->getCalculatedValue()))) {
                        $prevRowHasData = true;
                        break;
                    }
                }
                
                if ($prevRowHasData) {
                    $emptyAreas[] = "Row {$row}: Completely empty (good for new data)";
                }
            }
        }
        
        // Show first 10 empty areas
        foreach (array_slice($emptyAreas, 0, 10) as $area) {
            $io->text("  • {$area}");
        }
        
        if (count($emptyAreas) > 10) {
            $io->text("  ... and " . (count($emptyAreas) - 10) . " more areas");
        }
        
        if (empty($emptyAreas)) {
            $io->text("  No obvious empty areas found in first 50 rows");
        }
    }

    /**
     * Show specific recommendations for data insertion based on sheet names and structure
     */
    private function showDataInsertionRecommendations(string $templatePath, SymfonyStyle $io): void
    {
        $io->title('Data Insertion Recommendations');
        
        try {
            $spreadsheet = IOFactory::load($templatePath);
            
            foreach ($spreadsheet->getAllSheets() as $worksheet) {
                $sheetName = $worksheet->getTitle();
                
                // Focus on sheets that likely need data
                if (stripos($sheetName, 'data') !== false) {
                    $this->analyzeDataSheet($worksheet, $sheetName, $io);
                } elseif (in_array(strtolower($sheetName), ['summary contest', 'product', 'lotuss'])) {
                    $this->analyzeSummarySheet($worksheet, $sheetName, $io);
                } elseif ($this->isChannelSheet($sheetName)) {
                    $this->analyzeChannelSheet($worksheet, $sheetName, $io);
                }
            }
            
        } catch (\Exception $e) {
            $io->error("Error analyzing for recommendations: " . $e->getMessage());
        }
    }
    
    /**
     * Analyze data sheets for insertion points
     */
    private function analyzeDataSheet(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $worksheet, string $sheetName, SymfonyStyle $io): void
    {
        $io->section("📊 Data Sheet: {$sheetName}");
        
        // Look for header row
        $headerRow = 1;
        $headers = [];
        
        for ($col = 'A'; $col <= 'Z'; $col++) {
            $value = trim($worksheet->getCell($col . $headerRow)->getCalculatedValue());
            if (!empty($value)) {
                $headers[$col] = $value;
            }
        }
        
        if (!empty($headers)) {
            $io->text("🏷️  <comment>Headers found in row {$headerRow}:</comment>");
            foreach ($headers as $col => $header) {
                $io->text("  {$col}: {$header}");
            }
            
            // Look for data rows
            $io->text("💡 <comment>Recommended data insertion:</comment>");
            
            // Check if there are existing data rows
            $dataStartRow = $headerRow + 1;
            $hasExistingData = false;
            
            for ($row = $dataStartRow; $row <= $dataStartRow + 10; $row++) {
                $rowHasData = false;
                for ($col = 'A'; $col <= 'L'; $col++) {
                    if (!empty(trim($worksheet->getCell($col . $row)->getCalculatedValue()))) {
                        $rowHasData = true;
                        break;
                    }
                }
                if ($rowHasData) {
                    $hasExistingData = true;
                    break;
                }
            }
            
            if ($hasExistingData) {
                $io->text("  • Replace existing data starting from row {$dataStartRow}");
                $io->text("  • Or append new data after existing data");
            } else {
                $io->text("  • Insert data starting from row {$dataStartRow}");
            }
            
            // Show specific column recommendations
            foreach ($headers as $col => $header) {
                if (stripos($header, 'week') !== false) {
                    $io->text("  • Column {$col} ({$header}): Use ReportEntry->getWeekNumber()");
                } elseif (stripos($header, 'channel') !== false) {
                    $io->text("  • Column {$col} ({$header}): Use ReportByState->getChannel()");
                } elseif (stripos($header, 'entry') !== false || stripos($header, 'entries') !== false) {
                    $io->text("  • Column {$col} ({$header}): Use ReportByState->getEntries()");
                } elseif (stripos($header, 'state') !== false) {
                    $io->text("  • Column {$col} ({$header}): Use ReportByState->getState()");
                }
            }
        }
        
        $io->newLine();
    }
    
    /**
     * Analyze summary sheets for insertion points
     */
    private function analyzeSummarySheet(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $worksheet, string $sheetName, SymfonyStyle $io): void
    {
        $io->section("📈 Summary Sheet: {$sheetName}");
        
        // Look for week number reference
        for ($row = 1; $row <= 10; $row++) {
            for ($col = 'A'; $col <= 'E'; $col++) {
                $value = trim($worksheet->getCell($col . $row)->getCalculatedValue());
                if (stripos($value, 'week') !== false && preg_match('/week\s*(\d+)/i', $value)) {
                    $io->text("🗓️  <comment>Week reference found at {$col}{$row}: {$value}</comment>");
                    $io->text("💡 <comment>Recommendation:</comment>");
                    $io->text("  • Update cell {$col}{$row} with: \"Week {\$weekNumber}\"");
                    break 2;
                }
            }
        }
        
        // Look for data placeholders or empty cells that might need values
        $recommendations = [];
        
        for ($row = 1; $row <= 30; $row++) {
            for ($col = 'B'; $col <= 'M'; $col++) {
                $value = trim($worksheet->getCell($col . $row)->getCalculatedValue());
                $leftLabel = trim($worksheet->getCell('A' . $row)->getCalculatedValue());
                
                // If there's a label in column A and empty cell in other columns
                if (!empty($leftLabel) && empty($value)) {
                    $recommendations[] = "Cell {$col}{$row}: Empty cell next to '{$leftLabel}'";
                }
            }
        }
        
        if (!empty($recommendations)) {
            $io->text("🎯 <comment>Potential data insertion points:</comment>");
            foreach (array_slice($recommendations, 0, 10) as $rec) {
                $io->text("  • {$rec}");
            }
            if (count($recommendations) > 10) {
                $io->text("  ... and " . (count($recommendations) - 10) . " more");
            }
        }
        
        $io->newLine();
    }
    
    /**
     * Analyze channel sheets for detailed structure
     */
    private function analyzeChannelSheet(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $worksheet, string $sheetName, SymfonyStyle $io): void
    {
        $io->section("📊 Channel Sheet: {$sheetName}");
        
        // Look for week reference
        for ($row = 1; $row <= 5; $row++) {
            for ($col = 'A'; $col <= 'E'; $col++) {
                $value = trim($worksheet->getCell($col . $row)->getCalculatedValue());
                if (stripos($value, 'week') !== false && preg_match('/week\s*(\d+)/i', $value)) {
                    $io->text("🗓️  Week reference found at {$col}{$row}: {$value}");
                    break 2;
                }
            }
        }
        
        // Show detailed structure for first 25 rows and columns A-P
        $io->text("🔍 Detailed structure (rows 1-25, columns A-P):");
        for ($row = 1; $row <= 25; $row++) {
            $rowData = [];
            $hasData = false;
            
            for ($col = 'A'; $col <= 'P'; $col++) {
                $cell = $worksheet->getCell($col . $row);
                $value = trim($cell->getCalculatedValue());
                
                if (!empty($value)) {
                    $hasData = true;
                    $rowData[$col] = $value;
                }
            }
            
            if ($hasData) {
                $io->text("  Row {$row}: " . json_encode($rowData, JSON_UNESCAPED_UNICODE));
            }
        }
        
        // Special focus on rows 20-22, columns C-L for age groups
        $io->text("🎯 Special focus on rows 20-22, columns C-L (potential age groups):");
        for ($row = 20; $row <= 22; $row++) {
            $rowData = [];
            $hasData = false;
            
            for ($col = 'C'; $col <= 'L'; $col++) {
                $cell = $worksheet->getCell($col . $row);
                $value = trim($cell->getCalculatedValue());
                
                if (!empty($value)) {
                    $hasData = true;
                    $rowData[$col] = $value;
                }
            }
            
            if ($hasData) {
                $io->text("  Row {$row}: " . json_encode($rowData, JSON_UNESCAPED_UNICODE));
            } else {
                $io->text("  Row {$row}: Empty");
            }
        }
        
        $io->newLine();
    }

    /**
     * Check if sheet name represents a channel-specific sheet
     */
    private function isChannelSheet(string $sheetName): bool
    {
        $channelNames = ['SHM', 'MONT', 'TONT', 'CVS', 'LOTUSS', 'S99', 'ECOMM', 'TOFT'];
        
        foreach ($channelNames as $channel) {
            if (stripos($sheetName, $channel) !== false) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Extract channel name from sheet name
     */
    private function extractChannelFromSheetName(string $sheetName): string
    {
        $channelNames = ['SHM', 'MONT', 'TONT', 'CVS', 'LOTUSS', 'S99', 'ECOMM', 'TOFT'];
        
        foreach ($channelNames as $channel) {
            if (stripos($sheetName, $channel) !== false) {
                return $channel;
            }
        }
        
        return strtoupper($sheetName);
    }
    
    /**
     * Filter ReportByState data by specific channel
     */
    private function filterDataByChannel(array $reportByStateData, string $channelName): array
    {
        return array_filter($reportByStateData, function($report) use ($channelName) {
            // Assuming ReportByState has a getChannel() method
            return strtoupper($report->getChannel()) === strtoupper($channelName);
        });
    }
    
    /**
     * Populate channel-specific sheet with cumulative data from weeks 1 to N
     */
    private function populateChannelSheet(
        \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $worksheet, 
        array $channelData, 
        string $channelName, 
        int $weekNumber, 
        SymfonyStyle $io
    ): void {
        // Update week reference in cell A2 if it exists
        $weekCell = $worksheet->getCell('A2');
        $currentValue = $weekCell->getCalculatedValue();
        if (stripos($currentValue, 'week') !== false) {
            $newValue = preg_replace('/week\s*\d+/i', "Week {$weekNumber}", $currentValue);
            $weekCell->setValue($newValue);
            $io->text("    ✓ Updated week reference: {$newValue}");
        }
        
        $io->text("  📊 Populating {$channelName} sheet with cumulative data (Weeks 1-{$weekNumber})");
        
        // Week column mapping for 2025
        $weekColumnMap = [
            1 => 'K',   // Week 1 (2025)
            2 => 'M',   // Week 2 (2025)
            3 => 'O',   // Week 3 (2025)
            4 => 'Q',   // Week 4 (2025)
            5 => 'S',   // Week 5 (2025)
            6 => 'U',   // Week 6 (2025)
            7 => 'W',   // Week 7 (2025)
            8 => 'Y',   // Week 8 (2025)
            9 => 'AA',  // Week 9 (2025)
            10 => 'AC', // Week 10 (2025)
        ];
        
        // Initialize cumulative totals
        $cumulativeTotal = 0;
        $cumulativeValid = 0;
        $cumulativeInvalid = 0;
        $cumulativePending = 0;
        
        // Populate data for each week from 1 to weekNumber
        for ($week = 1; $week <= $weekNumber; $week++) {
            $reportEntry = $this->getReportEntryData($week);
            
            if (!$reportEntry) {
                $io->text("    ⚠️  No data found for week {$week}, skipping");
                continue;
            }
            
            // Get channel data from ReportEntry
            $weekData = $this->getChannelDataFromReportEntry($reportEntry, $channelName);
            if (!$weekData) {
                continue; // Skip if channel not found
            }
            
            $weekColumn = $weekColumnMap[$week] ?? null;
            if (!$weekColumn) {
                $io->text("    ⚠️  No column mapping for week {$week}, skipping");
                continue;
            }
            
            $io->text("    📅 Week {$week} → Column {$weekColumn}: Total={$weekData['total']}, Valid={$weekData['valid']}, Invalid={$weekData['invalid']}, Pending={$weekData['pending']}");
            
            // Update individual week columns
            $this->updateWeekDataInSheet($worksheet, $weekColumn, $weekData, $io);
            
            // Add to cumulative totals
            $cumulativeTotal += $weekData['total'];
            $cumulativeValid += $weekData['valid'];
            $cumulativeInvalid += $weekData['invalid'];
            $cumulativePending += $weekData['pending'];
        }
        
        // Update cumulative column E with totals
        $io->text("    📊 Updating cumulative totals in Column E: Total={$cumulativeTotal}, Valid={$cumulativeValid}, Invalid={$cumulativeInvalid}, Pending={$cumulativePending}");
        $cumulativeData = [
            'total' => $cumulativeTotal,
            'valid' => $cumulativeValid,
            'invalid' => $cumulativeInvalid,
            'pending' => $cumulativePending
        ];
        $this->updateWeekDataInSheet($worksheet, 'E', $cumulativeData, $io);
        
        // Calculate and populate Column F (% WTD vs 2024) and Column H (WTD vs Benchmark)
        $this->updateCalculatedColumns($worksheet, $io);
        
        // Set future weeks (beyond weekNumber) to 0 instead of #REF!
        $this->setFutureWeeksToZero($worksheet, $weekNumber, $io);
        
        $io->text("    ✅ Completed {$channelName} sheet population (Weeks 1-{$weekNumber} + Cumulative + Calculations)");
    }
    
    /**
     * Get channel data from ReportEntry based on channel name
     */
    private function getChannelDataFromReportEntry(\App\Entity\ReportEntry $reportEntry, string $channelName): ?array
    {
        $channelLower = strtolower($channelName);
        
        switch ($channelLower) {
            case 'shm':
                return [
                    'total' => $reportEntry->getShmTotal() ?? 0,
                    'valid' => $reportEntry->getShmValid() ?? 0,
                    'invalid' => $reportEntry->getShmInvalid() ?? 0,
                    'pending' => $reportEntry->getShmPending() ?? 0,
                ];
            case 's99':
                return [
                    'total' => $reportEntry->getS99Total() ?? 0,
                    'valid' => $reportEntry->getS99Valid() ?? 0,
                    'invalid' => $reportEntry->getS99Invalid() ?? 0,
                    'pending' => $reportEntry->getS99Pending() ?? 0,
                ];
            case 'mont':
                return [
                    'total' => $reportEntry->getMontTotal() ?? 0,
                    'valid' => $reportEntry->getMontValid() ?? 0,
                    'invalid' => $reportEntry->getMontInvalid() ?? 0,
                    'pending' => $reportEntry->getMontPending() ?? 0,
                ];
            case 'tont':
                return [
                    'total' => $reportEntry->getTontTotal() ?? 0,
                    'valid' => $reportEntry->getTontValid() ?? 0,
                    'invalid' => $reportEntry->getTontInvalid() ?? 0,
                    'pending' => $reportEntry->getTontPending() ?? 0,
                ];
            case 'cvs':
                return [
                    'total' => $reportEntry->getCvsTotal() ?? 0,
                    'valid' => $reportEntry->getCvsValid() ?? 0,
                    'invalid' => $reportEntry->getCvsInvalid() ?? 0,
                    'pending' => $reportEntry->getCvsPending() ?? 0,
                ];
            case 'toft':
                return [
                    'total' => $reportEntry->getToftTotal() ?? 0,
                    'valid' => $reportEntry->getToftValid() ?? 0,
                    'invalid' => $reportEntry->getToftInvalid() ?? 0,
                    'pending' => $reportEntry->getToftPending() ?? 0,
                ];
            case 'ecomm':
                return [
                    'total' => $reportEntry->getEcommTotal() ?? 0,
                    'valid' => $reportEntry->getEcommValid() ?? 0,
                    'invalid' => $reportEntry->getEcommInvalid() ?? 0,
                    'pending' => $reportEntry->getEcommPending() ?? 0,
                ];
            case 'lotuss':
                // LOTUSS is not in ReportEntry, return null
                return null;
            default:
                return null;
        }
    }
    
    /**
     * Populate demographics section (rows 20-22) with age group and gender data
     */
    private function populateDemographicsSection(
        \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $worksheet,
        ?\App\Entity\ReportEntry $reportEntry,
        string $channelName,
        int $weekNumber,
        SymfonyStyle $io
    ): void {
        if (!$reportEntry) {
            $io->text("    ⚠️  No ReportEntry data available for demographics");
            return;
        }
        
        $io->text("  👥 Populating demographics section for {$channelName}");
        
        // Get cumulative demographics data for weeks 1 to weekNumber
        $cumulativeDemographics = $this->getCumulativeDemographicsData($channelName, $weekNumber);
        
        if (empty($cumulativeDemographics)) {
            $io->text("    ⚠️  No demographics data found for {$channelName}");
            return;
        }
        
        // Age group column mapping
        $ageGroupColumns = [
            'total' => 'C',      // Total
            '21_25' => 'D',      // 21-25
            '26_30' => 'E',      // 26-30
            '31_35' => 'F',      // 31-35
            '36_40' => 'H',      // 36-40
            '41_45' => 'J',      // 41-45
            '46_50' => 'K',      // 46-50
            '50_above' => 'L',   // 50-ABOVE
        ];
        
        // Row 20: Total by age group
        $io->text("    📊 Row 20: Total demographics by age group");
        foreach ($ageGroupColumns as $ageGroup => $column) {
            $value = $cumulativeDemographics['total'][$ageGroup] ?? 0;
            $worksheet->getCell($column . '20')->setValue($value);
            $io->text("      {$column}20: {$value} ({$ageGroup})");
        }
        
        // Row 21: Male by age group
        $io->text("    👨 Row 21: Male demographics by age group");
        foreach ($ageGroupColumns as $ageGroup => $column) {
            $value = $cumulativeDemographics['male'][$ageGroup] ?? 0;
            $worksheet->getCell($column . '21')->setValue($value);
            $io->text("      {$column}21: {$value} (male {$ageGroup})");
        }
        
        // Row 22: Female by age group
        $io->text("    👩 Row 22: Female demographics by age group");
        foreach ($ageGroupColumns as $ageGroup => $column) {
            $value = $cumulativeDemographics['female'][$ageGroup] ?? 0;
            $worksheet->getCell($column . '22')->setValue($value);
            $io->text("      {$column}22: {$value} (female {$ageGroup})");
        }
        
        $io->text("    ✅ Completed demographics population for {$channelName}");
    }
    
    /**
     * Get cumulative demographics data for a channel across multiple weeks
     */
    private function getCumulativeDemographicsData(string $channelName, int $weekNumber): array
    {
        $cumulativeData = [
            'total' => [
                'total' => 0,
                '21_25' => 0,
                '26_30' => 0,
                '31_35' => 0,
                '36_40' => 0,
                '41_45' => 0,
                '46_50' => 0,
                '50_above' => 0,
            ],
            'male' => [
                'total' => 0,
                '21_25' => 0,
                '26_30' => 0,
                '31_35' => 0,
                '36_40' => 0,
                '41_45' => 0,
                '46_50' => 0,
                '50_above' => 0,
            ],
            'female' => [
                'total' => 0,
                '21_25' => 0,
                '26_30' => 0,
                '31_35' => 0,
                '36_40' => 0,
                '41_45' => 0,
                '46_50' => 0,
                '50_above' => 0,
            ],
        ];
        
        // Accumulate data from weeks 1 to weekNumber
        for ($week = 1; $week <= $weekNumber; $week++) {
            $reportEntry = $this->getReportEntryData($week);
            if (!$reportEntry) {
                continue;
            }
            
            $weekDemographics = $this->getChannelDemographicsFromReportEntry($reportEntry, $channelName);
            if (!$weekDemographics) {
                continue;
            }
            
            // Add to cumulative totals
            foreach (['total', 'male', 'female'] as $genderType) {
                foreach ($cumulativeData[$genderType] as $ageGroup => $value) {
                    $cumulativeData[$genderType][$ageGroup] += $weekDemographics[$genderType][$ageGroup] ?? 0;
                }
            }
        }
        
        return $cumulativeData;
    }
    
    /**
     * Get demographics data for a specific channel from ReportEntry
     */
    private function getChannelDemographicsFromReportEntry(\App\Entity\ReportEntry $reportEntry, string $channelName): ?array
    {
        $channelLower = strtolower($channelName);
        
        switch ($channelLower) {
            case 'shm':
                return [
                    'total' => [
                        'total' => $reportEntry->getShmTotal() ?? 0,
                        '21_25' => $reportEntry->getShmAge2125() ?? 0,
                        '26_30' => $reportEntry->getShmAge2630() ?? 0,
                        '31_35' => $reportEntry->getShmAge3135() ?? 0,
                        '36_40' => $reportEntry->getShmAge3640() ?? 0,
                        '41_45' => $reportEntry->getShmAge4145() ?? 0,
                        '46_50' => $reportEntry->getShmAge4650() ?? 0,
                        '50_above' => $reportEntry->getShmAge50Above() ?? 0,
                    ],
                    'male' => [
                        'total' => $reportEntry->getMaleEntryShm() ?? 0,
                        '21_25' => $reportEntry->getShmMaleAge2125() ?? 0,
                        '26_30' => $reportEntry->getShmMaleAge2630() ?? 0,
                        '31_35' => $reportEntry->getShmMaleAge3135() ?? 0,
                        '36_40' => $reportEntry->getShmMaleAge3640() ?? 0,
                        '41_45' => $reportEntry->getShmMaleAge4145() ?? 0,
                        '46_50' => $reportEntry->getShmMaleAge4650() ?? 0,
                        '50_above' => $reportEntry->getShmMaleAge50Above() ?? 0,
                    ],
                    'female' => [
                        'total' => $reportEntry->getFemaleEntryShm() ?? 0,
                        '21_25' => $reportEntry->getShmFemaleAge2125() ?? 0,
                        '26_30' => $reportEntry->getShmFemaleAge2630() ?? 0,
                        '31_35' => $reportEntry->getShmFemaleAge3135() ?? 0,
                        '36_40' => $reportEntry->getShmFemaleAge3640() ?? 0,
                        '41_45' => $reportEntry->getShmFemaleAge4145() ?? 0,
                        '46_50' => $reportEntry->getShmFemaleAge4650() ?? 0,
                        '50_above' => $reportEntry->getShmFemaleAge50Above() ?? 0,
                    ],
                ];
            case 's99':
                return [
                    'total' => [
                        'total' => $reportEntry->getS99Total() ?? 0,
                        '21_25' => $reportEntry->getS99Age2125() ?? 0,
                        '26_30' => $reportEntry->getS99Age2630() ?? 0,
                        '31_35' => $reportEntry->getS99Age3135() ?? 0,
                        '36_40' => $reportEntry->getS99Age3640() ?? 0,
                        '41_45' => $reportEntry->getS99Age4145() ?? 0,
                        '46_50' => $reportEntry->getS99Age4650() ?? 0,
                        '50_above' => $reportEntry->getS99Age50Above() ?? 0,
                    ],
                    'male' => [
                        'total' => $reportEntry->getMaleEntryS99() ?? 0,
                        '21_25' => $reportEntry->getS99MaleAge2125() ?? 0,
                        '26_30' => $reportEntry->getS99MaleAge2630() ?? 0,
                        '31_35' => $reportEntry->getS99MaleAge3135() ?? 0,
                        '36_40' => $reportEntry->getS99MaleAge3640() ?? 0,
                        '41_45' => $reportEntry->getS99MaleAge4145() ?? 0,
                        '46_50' => $reportEntry->getS99MaleAge4650() ?? 0,
                        '50_above' => $reportEntry->getS99MaleAge50Above() ?? 0,
                    ],
                    'female' => [
                        'total' => $reportEntry->getFemaleEntryS99() ?? 0,
                        '21_25' => $reportEntry->getS99FemaleAge2125() ?? 0,
                        '26_30' => $reportEntry->getS99FemaleAge2630() ?? 0,
                        '31_35' => $reportEntry->getS99FemaleAge3135() ?? 0,
                        '36_40' => $reportEntry->getS99FemaleAge3640() ?? 0,
                        '41_45' => $reportEntry->getS99FemaleAge4145() ?? 0,
                        '46_50' => $reportEntry->getS99FemaleAge4650() ?? 0,
                        '50_above' => $reportEntry->getS99FemaleAge50Above() ?? 0,
                    ],
                ];
            case 'mont':
                return [
                    'total' => [
                        'total' => $reportEntry->getMontTotal() ?? 0,
                        '21_25' => $reportEntry->getMontAge2125() ?? 0,
                        '26_30' => $reportEntry->getMontAge2630() ?? 0,
                        '31_35' => $reportEntry->getMontAge3135() ?? 0,
                        '36_40' => $reportEntry->getMontAge3640() ?? 0,
                        '41_45' => $reportEntry->getMontAge4145() ?? 0,
                        '46_50' => $reportEntry->getMontAge4650() ?? 0,
                        '50_above' => $reportEntry->getMontAge50Above() ?? 0,
                    ],
                    'male' => [
                        'total' => $reportEntry->getMaleEntryMont() ?? 0,
                        '21_25' => $reportEntry->getMontMaleAge2125() ?? 0,
                        '26_30' => $reportEntry->getMontMaleAge2630() ?? 0,
                        '31_35' => $reportEntry->getMontMaleAge3135() ?? 0,
                        '36_40' => $reportEntry->getMontMaleAge3640() ?? 0,
                        '41_45' => $reportEntry->getMontMaleAge4145() ?? 0,
                        '46_50' => $reportEntry->getMontMaleAge4650() ?? 0,
                        '50_above' => $reportEntry->getMontMaleAge50Above() ?? 0,
                    ],
                    'female' => [
                        'total' => $reportEntry->getFemaleEntryMont() ?? 0,
                        '21_25' => $reportEntry->getMontFemaleAge2125() ?? 0,
                        '26_30' => $reportEntry->getMontFemaleAge2630() ?? 0,
                        '31_35' => $reportEntry->getMontFemaleAge3135() ?? 0,
                        '36_40' => $reportEntry->getMontFemaleAge3640() ?? 0,
                        '41_45' => $reportEntry->getMontFemaleAge4145() ?? 0,
                        '46_50' => $reportEntry->getMontFemaleAge4650() ?? 0,
                        '50_above' => $reportEntry->getMontFemaleAge50Above() ?? 0,
                    ],
                ];
            case 'tont':
                return [
                    'total' => [
                        'total' => $reportEntry->getTontTotal() ?? 0,
                        '21_25' => $reportEntry->getTontAge2125() ?? 0,
                        '26_30' => $reportEntry->getTontAge2630() ?? 0,
                        '31_35' => $reportEntry->getTontAge3135() ?? 0,
                        '36_40' => $reportEntry->getTontAge3640() ?? 0,
                        '41_45' => $reportEntry->getTontAge4145() ?? 0,
                        '46_50' => $reportEntry->getTontAge4650() ?? 0,
                        '50_above' => $reportEntry->getTontAge50Above() ?? 0,
                    ],
                    'male' => [
                        'total' => $reportEntry->getMaleEntryTont() ?? 0,
                        '21_25' => $reportEntry->getTontMaleAge2125() ?? 0,
                        '26_30' => $reportEntry->getTontMaleAge2630() ?? 0,
                        '31_35' => $reportEntry->getTontMaleAge3135() ?? 0,
                        '36_40' => $reportEntry->getTontMaleAge3640() ?? 0,
                        '41_45' => $reportEntry->getTontMaleAge4145() ?? 0,
                        '46_50' => $reportEntry->getTontMaleAge4650() ?? 0,
                        '50_above' => $reportEntry->getTontMaleAge50Above() ?? 0,
                    ],
                    'female' => [
                        'total' => $reportEntry->getFemaleEntryTont() ?? 0,
                        '21_25' => $reportEntry->getTontFemaleAge2125() ?? 0,
                        '26_30' => $reportEntry->getTontFemaleAge2630() ?? 0,
                        '31_35' => $reportEntry->getTontFemaleAge3135() ?? 0,
                        '36_40' => $reportEntry->getTontFemaleAge3640() ?? 0,
                        '41_45' => $reportEntry->getTontFemaleAge4145() ?? 0,
                        '46_50' => $reportEntry->getTontFemaleAge4650() ?? 0,
                        '50_above' => $reportEntry->getTontFemaleAge50Above() ?? 0,
                    ],
                ];
            case 'cvs':
                return [
                    'total' => [
                        'total' => $reportEntry->getCvsTotal() ?? 0,
                        '21_25' => $reportEntry->getCvsAge2125() ?? 0,
                        '26_30' => $reportEntry->getCvsAge2630() ?? 0,
                        '31_35' => $reportEntry->getCvsAge3135() ?? 0,
                        '36_40' => $reportEntry->getCvsAge3640() ?? 0,
                        '41_45' => $reportEntry->getCvsAge4145() ?? 0,
                        '46_50' => $reportEntry->getCvsAge4650() ?? 0,
                        '50_above' => $reportEntry->getCvsAge50Above() ?? 0,
                    ],
                    'male' => [
                        'total' => $reportEntry->getMaleEntryCvs() ?? 0,
                        '21_25' => $reportEntry->getCvsMaleAge2125() ?? 0,
                        '26_30' => $reportEntry->getCvsMaleAge2630() ?? 0,
                        '31_35' => $reportEntry->getCvsMaleAge3135() ?? 0,
                        '36_40' => $reportEntry->getCvsMaleAge3640() ?? 0,
                        '41_45' => $reportEntry->getCvsMaleAge4145() ?? 0,
                        '46_50' => $reportEntry->getCvsMaleAge4650() ?? 0,
                        '50_above' => $reportEntry->getCvsMaleAge50Above() ?? 0,
                    ],
                    'female' => [
                        'total' => $reportEntry->getFemaleEntryCvs() ?? 0,
                        '21_25' => $reportEntry->getCvsFemaleAge2125() ?? 0,
                        '26_30' => $reportEntry->getCvsFemaleAge2630() ?? 0,
                        '31_35' => $reportEntry->getCvsFemaleAge3135() ?? 0,
                        '36_40' => $reportEntry->getCvsFemaleAge3640() ?? 0,
                        '41_45' => $reportEntry->getCvsFemaleAge4145() ?? 0,
                        '46_50' => $reportEntry->getCvsFemaleAge4650() ?? 0,
                        '50_above' => $reportEntry->getCvsFemaleAge50Above() ?? 0,
                    ],
                ];
            case 'toft':
                return [
                    'total' => [
                        'total' => $reportEntry->getToftTotal() ?? 0,
                        '21_25' => $reportEntry->getToftAge2125() ?? 0,
                        '26_30' => $reportEntry->getToftAge2630() ?? 0,
                        '31_35' => $reportEntry->getToftAge3135() ?? 0,
                        '36_40' => $reportEntry->getToftAge3640() ?? 0,
                        '41_45' => $reportEntry->getToftAge4145() ?? 0,
                        '46_50' => $reportEntry->getToftAge4650() ?? 0,
                        '50_above' => $reportEntry->getToftAge50Above() ?? 0,
                    ],
                    'male' => [
                        'total' => $reportEntry->getMaleEntryToft() ?? 0,
                        '21_25' => $reportEntry->getToftMaleAge2125() ?? 0,
                        '26_30' => $reportEntry->getToftMaleAge2630() ?? 0,
                        '31_35' => $reportEntry->getToftMaleAge3135() ?? 0,
                        '36_40' => $reportEntry->getToftMaleAge3640() ?? 0,
                        '41_45' => $reportEntry->getToftMaleAge4145() ?? 0,
                        '46_50' => $reportEntry->getToftMaleAge4650() ?? 0,
                        '50_above' => $reportEntry->getToftMaleAge50Above() ?? 0,
                    ],
                    'female' => [
                        'total' => $reportEntry->getFemaleEntryToft() ?? 0,
                        '21_25' => $reportEntry->getToftFemaleAge2125() ?? 0,
                        '26_30' => $reportEntry->getToftFemaleAge2630() ?? 0,
                        '31_35' => $reportEntry->getToftFemaleAge3135() ?? 0,
                        '36_40' => $reportEntry->getToftFemaleAge3640() ?? 0,
                        '41_45' => $reportEntry->getToftFemaleAge4145() ?? 0,
                        '46_50' => $reportEntry->getToftFemaleAge4650() ?? 0,
                        '50_above' => $reportEntry->getToftFemaleAge50Above() ?? 0,
                    ],
                ];
            case 'ecomm':
                return [
                    'total' => [
                        'total' => $reportEntry->getEcommTotal() ?? 0,
                        '21_25' => $reportEntry->getEcommAge2125() ?? 0,
                        '26_30' => $reportEntry->getEcommAge2630() ?? 0,
                        '31_35' => $reportEntry->getEcommAge3135() ?? 0,
                        '36_40' => $reportEntry->getEcommAge3640() ?? 0,
                        '41_45' => $reportEntry->getEcommAge4145() ?? 0,
                        '46_50' => $reportEntry->getEcommAge4650() ?? 0,
                        '50_above' => $reportEntry->getEcommAge50Above() ?? 0,
                    ],
                    'male' => [
                        'total' => $reportEntry->getMaleEntryEcomm() ?? 0,
                        '21_25' => $reportEntry->getEcommMaleAge2125() ?? 0,
                        '26_30' => $reportEntry->getEcommMaleAge2630() ?? 0,
                        '31_35' => $reportEntry->getEcommMaleAge3135() ?? 0,
                        '36_40' => $reportEntry->getEcommMaleAge3640() ?? 0,
                        '41_45' => $reportEntry->getEcommMaleAge4145() ?? 0,
                        '46_50' => $reportEntry->getEcommMaleAge4650() ?? 0,
                        '50_above' => $reportEntry->getEcommMaleAge50Above() ?? 0,
                    ],
                    'female' => [
                        'total' => $reportEntry->getFemaleEntryEcomm() ?? 0,
                        '21_25' => $reportEntry->getEcommFemaleAge2125() ?? 0,
                        '26_30' => $reportEntry->getEcommFemaleAge2630() ?? 0,
                        '31_35' => $reportEntry->getEcommFemaleAge3135() ?? 0,
                        '36_40' => $reportEntry->getEcommFemaleAge3640() ?? 0,
                        '41_45' => $reportEntry->getEcommFemaleAge4145() ?? 0,
                        '46_50' => $reportEntry->getEcommFemaleAge4650() ?? 0,
                        '50_above' => $reportEntry->getEcommFemaleAge50Above() ?? 0,
                    ],
                ];
            default:
                return null;
        }
    }
    
    /**
     * Populate rejection reasons section (rows 14-15) with rejection count data
     */
    private function populateRejectionReasonsSection(
        \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $worksheet,
        ?\App\Entity\ReportEntry $reportEntry,
        string $channelName,
        int $weekNumber,
        SymfonyStyle $io
    ): void {
        if (!$reportEntry) {
            $io->text("    ⚠️  No ReportEntry data available for rejection reasons");
            return;
        }
        
        $io->text("  🚫 Populating rejection reasons section for {$channelName}");
        
        // Get cumulative rejection reasons data for weeks 1 to weekNumber
        $cumulativeRejections = $this->getCumulativeRejectionReasonsData($channelName, $weekNumber);
        
        if (empty($cumulativeRejections)) {
            $io->text("    ⚠️  No rejection reasons data found for {$channelName}");
            return;
        }
        
        // Rejection reason column mapping (8 reasons across columns C-L, but skipping some columns)
        $rejectionColumns = [
            'reason1' => 'C',    // Reason 1
            'reason2' => 'D',    // Reason 2
            'reason3' => 'E',    // Reason 3
            'reason4' => 'F',    // Reason 4
            'reason5' => 'H',    // Reason 5 (skip G)
            'reason6' => 'J',    // Reason 6 (skip I)
            'reason7' => 'K',    // Reason 7
            'reason8' => 'L',    // Reason 8
        ];
        
        // Rejection reason labels mapping
        $rejectionLabels = [
            'reason1' => 'DUPLICATE RECEIPT',
            'reason2' => 'BELOW QUALIFYING QUANTITY',
            'reason3' => 'NON PARTICIPATING PRODUCT',
            'reason4' => 'OUTSIDE CONTEST PERIOD',
            'reason5' => 'UNCLEAR IMAGE/NOT A RECEIPT',
            'reason6' => 'BELOW QUALIFYING AMOUNT',
            'reason7' => 'NON PARTICIPATING OUTLET',
            'reason8' => 'OUTSIDE COVERAGE',
        ];
        
        // Row 14: Rejection reason labels
        $io->text("    🏷️  Row 14: Rejection reason labels");
        foreach ($rejectionColumns as $reasonKey => $column) {
            $label = $rejectionLabels[$reasonKey] ?? '';
            $worksheet->getCell($column . '14')->setValue($label);
            $io->text("      {$column}14: {$label}");
        }
        
        // Row 15: Rejection reasons count (cumulative weeks 1 to weekNumber)
        $io->text("    🚫 Row 15: Rejection reasons count (cumulative weeks 1-{$weekNumber})");
        
        // Calculate total rejections for column B
        $totalRejections = array_sum($cumulativeRejections);
        $worksheet->getCell('B15')->setValue($totalRejections);
        $io->text("      B15: {$totalRejections} (total rejections)");
        
        // Populate individual rejection reasons in columns C-L
        foreach ($rejectionColumns as $reasonKey => $column) {
            $value = $cumulativeRejections[$reasonKey] ?? 0;
            $worksheet->getCell($column . '15')->setValue($value);
            $io->text("      {$column}15: {$value} ({$reasonKey})");
        }
        
        $io->text("    ✅ Completed rejection reasons population for {$channelName}");
    }

    /**
     * Detailed analysis of Summary Contest sheet structure
     */
    private function analyzeSummaryContestSheet(string $templatePath, SymfonyStyle $io): void
    {
        $io->title('📊 Summary Contest Sheet - Detailed Analysis');
        
        try {
            $spreadsheet = IOFactory::load($templatePath);
            $summarySheet = null;
            
            // Find the Summary Contest sheet
            foreach ($spreadsheet->getAllSheets() as $worksheet) {
                if (stripos($worksheet->getTitle(), 'summary') !== false && stripos($worksheet->getTitle(), 'contest') !== false) {
                    $summarySheet = $worksheet;
                    break;
                }
            }
            
            if (!$summarySheet) {
                $io->error('Summary Contest sheet not found');
                return;
            }
            
            $io->info("Found Summary Contest sheet: " . $summarySheet->getTitle());
            $io->info("Dimensions: A1 to " . $summarySheet->getHighestColumn() . $summarySheet->getHighestRow());
            
            // Analyze structure in detail - look at first 50 rows
            $io->section('🔍 Detailed Structure Analysis (First 50 rows)');
            
            for ($row = 1; $row <= min(50, $summarySheet->getHighestRow()); $row++) {
                $rowData = [];
                $hasData = false;
                
                // Check columns A through P (16 columns)
                for ($col = 'A'; $col <= 'P'; $col++) {
                    $cell = $summarySheet->getCell($col . $row);
                    $value = trim($cell->getCalculatedValue());
                    
                    if (!empty($value) && $value !== '#REF!') {
                        $hasData = true;
                        $rowData[$col] = $value;
                    }
                }
                
                if ($hasData) {
                    $io->text("Row {$row}: " . json_encode($rowData, JSON_UNESCAPED_UNICODE));
                }
            }
            
            // Look for channel patterns
            $io->section('🏪 Channel Pattern Analysis');
            $channels = ['Total', 'SHM', 'MONT', 'TONT', 'CVS', 'TOFT', 'S99', 'ECOMM'];
            
            foreach ($channels as $channel) {
                $foundRows = [];
                for ($row = 1; $row <= min(50, $summarySheet->getHighestRow()); $row++) {
                    $cellA = trim($summarySheet->getCell('A' . $row)->getCalculatedValue());
                    if (stripos($cellA, $channel) !== false) {
                        $foundRows[] = $row;
                    }
                }
                
                if (!empty($foundRows)) {
                    $io->text("📍 {$channel} found at rows: " . implode(', ', $foundRows));
                }
            }
            
            // Analyze rejection table area (rows 50-58, columns B-L)
            $io->section('🚫 Rejection Table Analysis (Rows 50-58, Columns B-L)');
            
            for ($row = 50; $row <= min(58, $summarySheet->getHighestRow()); $row++) {
                $rowData = [];
                $hasData = false;
                
                for ($col = 'B'; $col <= 'L'; $col++) {
                    $cell = $summarySheet->getCell($col . $row);
                    $value = trim($cell->getCalculatedValue());
                    
                    if (!empty($value) && $value !== '#REF!') {
                        $hasData = true;
                        $rowData[$col] = $value;
                    }
                }
                
                if ($hasData) {
                    $io->text("🚫 Row {$row}: " . json_encode($rowData, JSON_UNESCAPED_UNICODE));
                }
            }
            
            // Analyze demographics/age group area (rows 60-90, columns A-L) - expanded range
            $io->section('👥 Demographics/Age Group Analysis (Rows 60-90, Columns A-L)');
            
            for ($row = 60; $row <= min(90, $summarySheet->getHighestRow()); $row++) {
                $rowData = [];
                $hasData = false;
                
                for ($col = 'A'; $col <= 'L'; $col++) {
                    $cell = $summarySheet->getCell($col . $row);
                    $value = trim($cell->getCalculatedValue());
                    
                    if (!empty($value) && $value !== '#REF!') {
                        $hasData = true;
                        $rowData[$col] = $value;
                    }
                }
                
                if ($hasData) {
                    $io->text("👥 Row {$row}: " . json_encode($rowData, JSON_UNESCAPED_UNICODE));
                }
            }
            
        } catch (\Exception $e) {
            $io->error("Error analyzing Summary Contest sheet: " . $e->getMessage());
        }
    }

    /**
     * Populate Summary Contest sheet with all channels data
     */
    private function populateSummaryContestSheet(
        \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $worksheet,
        ?ReportEntry $reportEntry,
        array $reportByStateData,
        int $weekNumber,
        SymfonyStyle $io
    ): void {
        $io->text("📊 Populating Summary Contest sheet with all channels data");
        
        // Update week reference in row 2
        $weekReference = " Week {$weekNumber} (30-3 Jul 25)";
        $worksheet->getCell('A2')->setValue($weekReference);
        $io->text("  ✓ Updated week reference: {$weekReference}");
        
        // Channel row mapping based on analysis
        $channelRows = [
            'Total' => 8,    // Total (all channels combined)
            'SHM' => 13,     // SHM channel
            'MONT' => 18,    // MONT channel  
            'TONT' => 23,    // TONT channel
            'CVS' => 28,     // CVS channel
            'TOFT' => 33,    // TOFT channel
            'S99' => 38,     // S99 (99SM) channel
            'ECOMM' => 43,   // ECOMM channel
        ];
        
        // Get cumulative data for all channels (weeks 1 to weekNumber)
        $allChannelsData = $this->getCumulativeDataForAllChannels($weekNumber);
        
        // Populate each channel section
        foreach ($channelRows as $channelName => $startRow) {
            $io->text("  📍 Populating {$channelName} section (rows {$startRow}-" . ($startRow + 3) . ")");
            
            if ($channelName === 'Total') {
                // Calculate totals across all channels
                $channelData = $this->calculateTotalAcrossAllChannels($allChannelsData);
            } else {
                // Get data for specific channel
                $channelData = $allChannelsData[$channelName] ?? [
                    'total' => 0, 'valid' => 0, 'invalid' => 0, 'pending' => 0
                ];
            }
            
            // Populate the 4 rows for this channel
            $this->populateChannelSectionInSummary($worksheet, $startRow, $channelName, $channelData, $weekNumber, $io);
        }
        
        // Populate rejection reasons table (rows 50-58, columns B-L)
        $this->populateRejectionReasonsTableInSummary($worksheet, $weekNumber, $io);
        
        // Populate demographics table (rows 63-86, columns C-L)
        $this->populateDemographicsTableInSummary($worksheet, $weekNumber, $io);
        
        $io->text("  ✅ Completed Summary Contest sheet population");
    }

    /**
     * Populate rejection reasons table in Summary Contest sheet (rows 50-58, columns B-L)
     */
    private function populateRejectionReasonsTableInSummary(
        \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $worksheet,
        int $weekNumber,
        SymfonyStyle $io
    ): void {
        $io->text("  🚫 Populating rejection reasons table (rows 50-58, columns B-L)");
        
        // Rejection reason column mapping (8 reasons across columns C-L, but skipping some columns)
        $rejectionColumns = [
            'reason1' => 'C',    // Reason 1
            'reason2' => 'D',    // Reason 2
            'reason3' => 'E',    // Reason 3
            'reason4' => 'F',    // Reason 4
            'reason5' => 'H',    // Reason 5 (skip G)
            'reason6' => 'J',    // Reason 6 (skip I)
            'reason7' => 'K',    // Reason 7
            'reason8' => 'L',    // Reason 8
        ];
        
        // Rejection reason labels mapping
        $rejectionLabels = [
            'reason1' => 'DUPLICATE RECEIPT',
            'reason2' => 'BELOW QUALIFYING QUANTITY',
            'reason3' => 'NON PARTICIPATING PRODUCT',
            'reason4' => 'OUTSIDE CONTEST PERIOD',
            'reason5' => 'UNCLEAR IMAGE/NOT A RECEIPT',
            'reason6' => 'BELOW QUALIFYING AMOUNT',
            'reason7' => 'NON PARTICIPATING OUTLET',
            'reason8' => 'OUTSIDE COVERAGE',
        ];
        
        // Get all channels
        $channels = ['SHM', 'MONT', 'TONT', 'CVS', 'TOFT', 'S99', 'ECOMM'];
        
        // Aggregate rejection reasons across all channels
        $totalRejections = [
            'reason1' => 0,
            'reason2' => 0,
            'reason3' => 0,
            'reason4' => 0,
            'reason5' => 0,
            'reason6' => 0,
            'reason7' => 0,
            'reason8' => 0,
        ];
        
        // Collect rejection data for each channel
        $channelRejections = [];
        
        foreach ($channels as $channel) {
            $channelRejectionData = $this->getCumulativeRejectionReasonsData($channel, $weekNumber);
            $channelRejections[$channel] = $channelRejectionData;
            
            // Add to total
            foreach ($totalRejections as $reasonKey => $value) {
                $totalRejections[$reasonKey] += $channelRejectionData[$reasonKey] ?? 0;
            }
        }
        
        // Row 50: Headers - rejection reason labels
        $io->text("    🏷️  Row 50: Rejection reason labels");
        foreach ($rejectionColumns as $reasonKey => $column) {
            $label = $rejectionLabels[$reasonKey] ?? '';
            $worksheet->getCell($column . '50')->setValue($label);
            $io->text("      {$column}50: {$label}");
        }
        
        // Row 51: Total across all channels
        $io->text("    🚫 Row 51: Total rejection reasons across all channels");
        
        // Calculate grand total for column B
        $grandTotal = array_sum($totalRejections);
        $worksheet->getCell('B51')->setValue($grandTotal);
        $io->text("      B51: {$grandTotal} (grand total rejections)");
        
        // Populate individual rejection reasons totals in columns C-L
        foreach ($rejectionColumns as $reasonKey => $column) {
            $value = $totalRejections[$reasonKey] ?? 0;
            $worksheet->getCell($column . '51')->setValue($value);
            $io->text("      {$column}51: {$value} ({$reasonKey})");
        }
        
        // Rows 52-58: Individual channel rejection breakdowns
        $channelRowMap = [
            'SHM' => 52,
            'MONT' => 53,
            'TONT' => 54,
            'CVS' => 55,
            'TOFT' => 56,
            'S99' => 57,
            'ECOMM' => 58,
        ];
        
        foreach ($channelRowMap as $channel => $row) {
            $io->text("    🚫 Row {$row}: {$channel} rejection reasons");
            
            $channelData = $channelRejections[$channel] ?? [];
            
            // Calculate channel total for column B
            $channelTotal = array_sum($channelData);
            $worksheet->getCell('B' . $row)->setValue($channelTotal);
            $io->text("      B{$row}: {$channelTotal} ({$channel} total rejections)");
            
            // Populate individual rejection reasons for this channel
            foreach ($rejectionColumns as $reasonKey => $column) {
                $value = $channelData[$reasonKey] ?? 0;
                $worksheet->getCell($column . $row)->setValue($value);
                $io->text("      {$column}{$row}: {$value} ({$channel} {$reasonKey})");
            }
        }
        
        $io->text("    ✅ Completed rejection reasons table population");
    }

    /**
     * Populate demographics table in Summary Contest sheet (rows 60-86, columns A-L)
     */
    private function populateDemographicsTableInSummary(
        \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $worksheet,
        int $weekNumber,
        SymfonyStyle $io
    ): void {
        $io->text("  👥 Populating demographics table (rows 60-86, columns A-L)");
        
        // Age group column mapping (8 age groups across columns C-L, but skipping some columns)
        $ageGroupColumns = [
            'total' => 'C',      // Total
            'age_21_25' => 'D',  // 21-25
            'age_26_30' => 'E',  // 26-30
            'age_31_35' => 'F',  // 31-35
            'age_36_40' => 'H',  // 36-40 (skip G)
            'age_41_45' => 'J',  // 41-45 (skip I)
            'age_46_50' => 'K',  // 46-50
            'age_50_above' => 'L', // 50-ABOVE
        ];
        
        // Age group labels mapping
        $ageGroupLabels = [
            'total' => 'Total',
            'age_21_25' => '21-25',
            'age_26_30' => '26-30',
            'age_31_35' => '31-35',
            'age_36_40' => '36-40',
            'age_41_45' => '41-45',
            'age_46_50' => '46-50',
            'age_50_above' => '50-ABOVE',
        ];
        
        // Get all channels
        $channels = ['SHM', 'MONT', 'TONT', 'CVS', 'TOFT', 'S99', 'ECOMM'];
        
        // Aggregate demographics across all channels
        $totalDemographics = [
            'total' => ['total' => 0, 'male' => 0, 'female' => 0],
            'age_21_25' => ['total' => 0, 'male' => 0, 'female' => 0],
            'age_26_30' => ['total' => 0, 'male' => 0, 'female' => 0],
            'age_31_35' => ['total' => 0, 'male' => 0, 'female' => 0],
            'age_36_40' => ['total' => 0, 'male' => 0, 'female' => 0],
            'age_41_45' => ['total' => 0, 'male' => 0, 'female' => 0],
            'age_46_50' => ['total' => 0, 'male' => 0, 'female' => 0],
            'age_50_above' => ['total' => 0, 'male' => 0, 'female' => 0],
        ];
        
        // Collect demographics data for each channel
        $channelDemographics = [];
        
        foreach ($channels as $channel) {
            $channelDemographicsData = $this->getCumulativeDemographicsData($channel, $weekNumber);
            $channelDemographics[$channel] = $this->transformDemographicsDataForSummary($channelDemographicsData);
            
            // Add to total
            $transformedData = $channelDemographics[$channel];
            foreach ($totalDemographics as $ageGroup => $genderData) {
                $totalDemographics[$ageGroup]['total'] += $transformedData[$ageGroup]['total'] ?? 0;
                $totalDemographics[$ageGroup]['male'] += $transformedData[$ageGroup]['male'] ?? 0;
                $totalDemographics[$ageGroup]['female'] += $transformedData[$ageGroup]['female'] ?? 0;
            }
        }
        
        // Row 60: Section header - "Demographics"
        $worksheet->getCell('A60')->setValue('Demographics');
        $io->text("    📋 A60: Demographics");
        
        // Row 61: Age Group header
        $worksheet->getCell('C61')->setValue('Age Group');
        $io->text("    📋 C61: Age Group");
        
        // Row 62: Age group labels
        $io->text("    🏷️  Row 62: Age group labels");
        foreach ($ageGroupColumns as $ageKey => $column) {
            $label = $ageGroupLabels[$ageKey] ?? '';
            $worksheet->getCell($column . '62')->setValue($label);
            $io->text("      {$column}62: {$label}");
        }
        
        // Row 63: TOTAL across all channels - Total
        $io->text("    👥 Row 63: TOTAL demographics across all channels (Total)");
        $worksheet->getCell('A63')->setValue('TOTAL');
        $worksheet->getCell('B63')->setValue('Total');
        
        foreach ($ageGroupColumns as $ageKey => $column) {
            $value = $totalDemographics[$ageKey]['total'] ?? 0;
            $worksheet->getCell($column . '63')->setValue($value);
            $io->text("      {$column}63: {$value} (total {$ageKey})");
        }
        
        // Row 64: Total across all channels - Male
        $io->text("    👥 Row 64: Total demographics across all channels (Male)");
        $worksheet->getCell('A64')->setValue('Total');
        $worksheet->getCell('B64')->setValue('Male');
        
        foreach ($ageGroupColumns as $ageKey => $column) {
            $value = $totalDemographics[$ageKey]['male'] ?? 0;
            $worksheet->getCell($column . '64')->setValue($value);
            $io->text("      {$column}64: {$value} (male {$ageKey})");
        }
        
        // Row 65: Total across all channels - Female
        $io->text("    👥 Row 65: Total demographics across all channels (Female)");
        $worksheet->getCell('B65')->setValue('Female');
        
        foreach ($ageGroupColumns as $ageKey => $column) {
            $value = $totalDemographics[$ageKey]['female'] ?? 0;
            $worksheet->getCell($column . '65')->setValue($value);
            $io->text("      {$column}65: {$value} (female {$ageKey})");
        }
        
        // Individual channel demographics breakdowns
        $channelRowMap = [
            'SHM' => 66,
            'MONT' => 69,
            'TONT' => 72,
            'CVS' => 75,
            'TOFT' => 78,
            'S99' => 81,
            'ECOMM' => 84,
        ];
        
        foreach ($channelRowMap as $channel => $startRow) {
            $io->text("    👥 Rows {$startRow}-" . ($startRow + 2) . ": {$channel} demographics");
            
            $channelData = $channelDemographics[$channel] ?? [];
            
            // Channel Total row
            $worksheet->getCell('A' . $startRow)->setValue($channel);
            $worksheet->getCell('B' . $startRow)->setValue('Total');
            foreach ($ageGroupColumns as $ageKey => $column) {
                $value = $channelData[$ageKey]['total'] ?? 0;
                $worksheet->getCell($column . $startRow)->setValue($value);
                $io->text("      {$column}{$startRow}: {$value} ({$channel} total {$ageKey})");
            }
            
            // Channel Male row
            $worksheet->getCell('B' . ($startRow + 1))->setValue('Male');
            foreach ($ageGroupColumns as $ageKey => $column) {
                $value = $channelData[$ageKey]['male'] ?? 0;
                $worksheet->getCell($column . ($startRow + 1))->setValue($value);
                $io->text("      {$column}" . ($startRow + 1) . ": {$value} ({$channel} male {$ageKey})");
            }
            
            // Channel Female row
            $worksheet->getCell('B' . ($startRow + 2))->setValue('Female');
            foreach ($ageGroupColumns as $ageKey => $column) {
                $value = $channelData[$ageKey]['female'] ?? 0;
                $worksheet->getCell($column . ($startRow + 2))->setValue($value);
                $io->text("      {$column}" . ($startRow + 2) . ": {$value} ({$channel} female {$ageKey})");
            }
        }
        
        $io->text("    ✅ Completed demographics table population");
    }

    /**
     * Transform demographics data from channel format to summary format
     */
    private function transformDemographicsDataForSummary(array $channelData): array
    {
        $transformed = [
            'total' => ['total' => 0, 'male' => 0, 'female' => 0],
            'age_21_25' => ['total' => 0, 'male' => 0, 'female' => 0],
            'age_26_30' => ['total' => 0, 'male' => 0, 'female' => 0],
            'age_31_35' => ['total' => 0, 'male' => 0, 'female' => 0],
            'age_36_40' => ['total' => 0, 'male' => 0, 'female' => 0],
            'age_41_45' => ['total' => 0, 'male' => 0, 'female' => 0],
            'age_46_50' => ['total' => 0, 'male' => 0, 'female' => 0],
            'age_50_above' => ['total' => 0, 'male' => 0, 'female' => 0],
        ];
        
        // Map age group keys
        $ageGroupMapping = [
            'total' => 'total',
            '21_25' => 'age_21_25',
            '26_30' => 'age_26_30',
            '31_35' => 'age_31_35',
            '36_40' => 'age_36_40',
            '41_45' => 'age_41_45',
            '46_50' => 'age_46_50',
            '50_above' => 'age_50_above',
        ];
        
        // Transform the data structure
        foreach ($ageGroupMapping as $originalKey => $newKey) {
            $transformed[$newKey]['total'] = ($channelData['total'][$originalKey] ?? 0) + 
                                           ($channelData['male'][$originalKey] ?? 0) + 
                                           ($channelData['female'][$originalKey] ?? 0);
            $transformed[$newKey]['male'] = $channelData['male'][$originalKey] ?? 0;
            $transformed[$newKey]['female'] = $channelData['female'][$originalKey] ?? 0;
        }
        
        return $transformed;
    }

    /**
     * Get cumulative data for all channels from weeks 1 to weekNumber
     */
    private function getCumulativeDataForAllChannels(int $weekNumber): array
    {
        $channels = ['SHM', 'MONT', 'TONT', 'CVS', 'TOFT', 'S99', 'ECOMM'];
        $allData = [];
        
        foreach ($channels as $channel) {
            $cumulativeData = [
                'total' => 0,
                'valid' => 0, 
                'invalid' => 0,
                'pending' => 0,
                'weekly' => [] // Store weekly breakdown
            ];
            
            // Get data for each week from 1 to weekNumber
            for ($week = 1; $week <= $weekNumber; $week++) {
                $weekData = $this->getChannelDataForWeek($channel, $week);
                
                $cumulativeData['total'] += $weekData['total'];
                $cumulativeData['valid'] += $weekData['valid'];
                $cumulativeData['invalid'] += $weekData['invalid'];
                $cumulativeData['pending'] += $weekData['pending'];
                
                $cumulativeData['weekly'][$week] = $weekData;
            }
            
            $allData[$channel] = $cumulativeData;
        }
        
        return $allData;
    }

    /**
     * Calculate totals across all channels
     */
    private function calculateTotalAcrossAllChannels(array $allChannelsData): array
    {
        $totals = [
            'total' => 0,
            'valid' => 0,
            'invalid' => 0, 
            'pending' => 0,
            'weekly' => []
        ];
        
        foreach ($allChannelsData as $channelData) {
            $totals['total'] += $channelData['total'];
            $totals['valid'] += $channelData['valid'];
            $totals['invalid'] += $channelData['invalid'];
            $totals['pending'] += $channelData['pending'];
            
            // Sum weekly data
            foreach ($channelData['weekly'] as $week => $weekData) {
                if (!isset($totals['weekly'][$week])) {
                    $totals['weekly'][$week] = ['total' => 0, 'valid' => 0, 'invalid' => 0, 'pending' => 0];
                }
                $totals['weekly'][$week]['total'] += $weekData['total'];
                $totals['weekly'][$week]['valid'] += $weekData['valid'];
                $totals['weekly'][$week]['invalid'] += $weekData['invalid'];
                $totals['weekly'][$week]['pending'] += $weekData['pending'];
            }
        }
        
        return $totals;
    }

    /**
     * Populate a channel section in Summary Contest sheet (4 rows: Total Participation, Valid, Invalid, Pending)
     */
    private function populateChannelSectionInSummary(
        \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $worksheet,
        int $startRow,
        string $channelName,
        array $channelData,
        int $weekNumber,
        SymfonyStyle $io
    ): void {
        // Get 2024 benchmark data for calculations
        $benchmark2024 = $this->get2024BenchmarkData($channelName);
        
        // Row 1: Total Participation
        $worksheet->getCell('C' . $startRow)->setValue($benchmark2024['total']); // 2024 benchmark
        $worksheet->getCell('D' . $startRow)->setValue($channelData['total']); // 2025 actual
        $worksheet->getCell('E' . $startRow)->setValue($channelData['total']); // Week 1-X cumulative
        
        // Calculate Column F (% WTD vs 2024) and Column H (WTD vs Benchmark)
        $percentChange = $benchmark2024['total'] > 0 ? (($channelData['total'] - $benchmark2024['total']) / $benchmark2024['total']) * 100 : 0;
        $benchmarkDiff = $channelData['total'] - $benchmark2024['total'];
        
        $worksheet->getCell('F' . $startRow)->setValue(round($percentChange, 4));
        $worksheet->getCell('H' . $startRow)->setValue($benchmarkDiff);
        
        // Row 2: Valid
        $worksheet->getCell('C' . ($startRow + 1))->setValue($benchmark2024['valid']);
        $worksheet->getCell('D' . ($startRow + 1))->setValue($channelData['valid']);
        $worksheet->getCell('E' . ($startRow + 1))->setValue($channelData['valid']);
        
        $validPercentChange = $benchmark2024['valid'] > 0 ? (($channelData['valid'] - $benchmark2024['valid']) / $benchmark2024['valid']) * 100 : 0;
        $validBenchmarkDiff = $channelData['valid'] - $benchmark2024['valid'];
        
        $worksheet->getCell('F' . ($startRow + 1))->setValue(round($validPercentChange, 4));
        $worksheet->getCell('H' . ($startRow + 1))->setValue($validBenchmarkDiff);
        
        // Row 3: Invalid  
        $worksheet->getCell('C' . ($startRow + 2))->setValue($benchmark2024['invalid']);
        $worksheet->getCell('D' . ($startRow + 2))->setValue($channelData['invalid']);
        $worksheet->getCell('E' . ($startRow + 2))->setValue($channelData['invalid']);
        
        $invalidPercentChange = $benchmark2024['invalid'] > 0 ? (($channelData['invalid'] - $benchmark2024['invalid']) / $benchmark2024['invalid']) * 100 : 0;
        $invalidBenchmarkDiff = $channelData['invalid'] - $benchmark2024['invalid'];
        
        $worksheet->getCell('F' . ($startRow + 2))->setValue(round($invalidPercentChange, 4));
        $worksheet->getCell('H' . ($startRow + 2))->setValue($invalidBenchmarkDiff);
        
        // Row 4: Pending
        $worksheet->getCell('C' . ($startRow + 3))->setValue($benchmark2024['pending']);
        $worksheet->getCell('D' . ($startRow + 3))->setValue($channelData['pending']);
        $worksheet->getCell('E' . ($startRow + 3))->setValue($channelData['pending']);
        
        $pendingPercentChange = $benchmark2024['pending'] > 0 ? (($channelData['pending'] - $benchmark2024['pending']) / $benchmark2024['pending']) * 100 : 0;
        $pendingBenchmarkDiff = $channelData['pending'] - $benchmark2024['pending'];
        
        $worksheet->getCell('F' . ($startRow + 3))->setValue(round($pendingPercentChange, 4));
        $worksheet->getCell('H' . ($startRow + 3))->setValue($pendingBenchmarkDiff);
        
        // Populate weekly data - separate 2024 and 2025 columns
        $weeklyColumns = [
            // Week 1
            1 => ['2024' => 'J', '2025' => 'K'],
            // Week 2  
            2 => ['2024' => 'L', '2025' => 'M'],
            // Week 3
            3 => ['2024' => 'N', '2025' => 'O'],
            // Week 4
            4 => ['2024' => 'P', '2025' => 'Q'],
            // Week 5
            5 => ['2024' => 'R', '2025' => 'S'],
            // Week 6
            6 => ['2024' => 'T', '2025' => 'U'],
            // Week 7
            7 => ['2024' => 'V', '2025' => 'W'],
            // Week 8
            8 => ['2024' => 'X', '2025' => 'Y'],
            // Week 9
            9 => ['2024' => 'Z', '2025' => 'AA'],
            // Week 10
            10 => ['2024' => 'AB', '2025' => 'AC'],
        ];
        
        for ($week = 1; $week <= 10; $week++) {
            $columns = $weeklyColumns[$week];
            $col2024 = $columns['2024'];
            $col2025 = $columns['2025'];
            
            if ($week <= $weekNumber && isset($channelData['weekly'][$week])) {
                // Current or past weeks - populate with actual data
                $weekData = $channelData['weekly'][$week];
                
                // 2024 columns - keep existing template data (don't override)
                // Only populate 2025 columns with actual data
                
                // Total Participation
                $worksheet->getCell($col2025 . $startRow)->setValue($weekData['total']);
                
                // Valid
                $worksheet->getCell($col2025 . ($startRow + 1))->setValue($weekData['valid']);
                
                // Invalid
                $worksheet->getCell($col2025 . ($startRow + 2))->setValue($weekData['invalid']);
                
                // Pending
                $worksheet->getCell($col2025 . ($startRow + 3))->setValue($weekData['pending']);
            } else {
                // Future weeks beyond current week - set only 2025 columns to 0
                // Leave 2024 columns untouched (they contain template/benchmark data)
                
                $worksheet->getCell($col2025 . $startRow)->setValue(0);
                $worksheet->getCell($col2025 . ($startRow + 1))->setValue(0);
                $worksheet->getCell($col2025 . ($startRow + 2))->setValue(0);
                $worksheet->getCell($col2025 . ($startRow + 3))->setValue(0);
            }
        }
        
        $io->text("    ✓ {$channelName}: Total={$channelData['total']}, Valid={$channelData['valid']}, Invalid={$channelData['invalid']}, Pending={$channelData['pending']}");
        $io->text("      📊 Calculations: %Change={$percentChange}%, Benchmark Diff={$benchmarkDiff}");
    }

    /**
     * Get channel data for a specific week
     */
    private function getChannelDataForWeek(string $channelName, int $weekNumber): array
    {
        // Get ReportEntry for this week
        $reportEntry = $this->entityManager
            ->getRepository(ReportEntry::class)
            ->findOneBy(['week_number' => $weekNumber]);
            
        if (!$reportEntry) {
            return ['total' => 0, 'valid' => 0, 'invalid' => 0, 'pending' => 0];
        }
        
        // Get the data based on channel name
        $methodMap = [
            'SHM' => ['getShmTotal', 'getShmValid', 'getShmInvalid', 'getShmPending'],
            'MONT' => ['getMontTotal', 'getMontValid', 'getMontInvalid', 'getMontPending'],
            'TONT' => ['getTontTotal', 'getTontValid', 'getTontInvalid', 'getTontPending'],
            'CVS' => ['getCvsTotal', 'getCvsValid', 'getCvsInvalid', 'getCvsPending'],
            'TOFT' => ['getToftTotal', 'getToftValid', 'getToftInvalid', 'getToftPending'],
            'S99' => ['getS99Total', 'getS99Valid', 'getS99Invalid', 'getS99Pending'],
            'ECOMM' => ['getEcommTotal', 'getEcommValid', 'getEcommInvalid', 'getEcommPending'],
        ];
        
        if (!isset($methodMap[$channelName])) {
            return ['total' => 0, 'valid' => 0, 'invalid' => 0, 'pending' => 0];
        }
        
        $methods = $methodMap[$channelName];
        
        return [
            'total' => $reportEntry->{$methods[0]}() ?? 0,
            'valid' => $reportEntry->{$methods[1]}() ?? 0,
            'invalid' => $reportEntry->{$methods[2]}() ?? 0,
            'pending' => $reportEntry->{$methods[3]}() ?? 0,
        ];
    }

    /**
     * Get 2024 benchmark data for a channel (for percentage calculations)
     */
    private function get2024BenchmarkData(string $channelName): array
    {
        // These are sample 2024 benchmark values - in a real system, 
        // you would fetch these from a database or configuration
        $benchmarks = [
            'Total' => ['total' => 8500, 'valid' => 6800, 'invalid' => 1700, 'pending' => 0],
            'SHM' => ['total' => 790, 'valid' => 485, 'invalid' => 305, 'pending' => 0],
            'MONT' => ['total' => 1950, 'valid' => 1275, 'invalid' => 675, 'pending' => 0],
            'TONT' => ['total' => 2100, 'valid' => 1890, 'invalid' => 210, 'pending' => 0],
            'CVS' => ['total' => 200, 'valid' => 160, 'invalid' => 40, 'pending' => 0],
            'TOFT' => ['total' => 45, 'valid' => 38, 'invalid' => 7, 'pending' => 0],
            'S99' => ['total' => 3200, 'valid' => 2800, 'invalid' => 400, 'pending' => 0],
            'ECOMM' => ['total' => 215, 'valid' => 152, 'invalid' => 63, 'pending' => 0],
        ];
        
        return $benchmarks[$channelName] ?? ['total' => 0, 'valid' => 0, 'invalid' => 0, 'pending' => 0];
    }
    
    /**
     * Get cumulative rejection reasons data for a channel across multiple weeks
     */
    private function getCumulativeRejectionReasonsData(string $channelName, int $weekNumber): array
    {
        $cumulativeData = [
            'reason1' => 0,
            'reason2' => 0,
            'reason3' => 0,
            'reason4' => 0,
            'reason5' => 0,
            'reason6' => 0,
            'reason7' => 0,
            'reason8' => 0,
        ];
        
        // Accumulate data from weeks 1 to weekNumber
        for ($week = 1; $week <= $weekNumber; $week++) {
            $reportEntry = $this->getReportEntryData($week);
            if (!$reportEntry) {
                continue;
            }
            
            $weekRejections = $this->getChannelRejectionReasonsFromReportEntry($reportEntry, $channelName);
            if (!$weekRejections) {
                continue;
            }
            
            // Add to cumulative totals
            foreach ($cumulativeData as $reasonKey => $value) {
                $cumulativeData[$reasonKey] += $weekRejections[$reasonKey] ?? 0;
            }
        }
        
        return $cumulativeData;
    }
    
    /**
     * Get rejection reasons data for a specific channel from ReportEntry
     */
    private function getChannelRejectionReasonsFromReportEntry(\App\Entity\ReportEntry $reportEntry, string $channelName): ?array
    {
        $channelLower = strtolower($channelName);
        
        switch ($channelLower) {
            case 'shm':
                return [
                    'reason1' => $reportEntry->getRejectReason1Shm() ?? 0,
                    'reason2' => $reportEntry->getRejectReason2Shm() ?? 0,
                    'reason3' => $reportEntry->getRejectReason3Shm() ?? 0,
                    'reason4' => $reportEntry->getRejectReason4Shm() ?? 0,
                    'reason5' => $reportEntry->getRejectReason5Shm() ?? 0,
                    'reason6' => $reportEntry->getRejectReason6Shm() ?? 0,
                    'reason7' => $reportEntry->getRejectReason7Shm() ?? 0,
                    'reason8' => $reportEntry->getRejectReason8Shm() ?? 0,
                ];
            case 's99':
                return [
                    'reason1' => $reportEntry->getRejectReason1S99() ?? 0,
                    'reason2' => $reportEntry->getRejectReason2S99() ?? 0,
                    'reason3' => $reportEntry->getRejectReason3S99() ?? 0,
                    'reason4' => $reportEntry->getRejectReason4S99() ?? 0,
                    'reason5' => $reportEntry->getRejectReason5S99() ?? 0,
                    'reason6' => $reportEntry->getRejectReason6S99() ?? 0,
                    'reason7' => $reportEntry->getRejectReason7S99() ?? 0,
                    'reason8' => $reportEntry->getRejectReason8S99() ?? 0,
                ];
            case 'mont':
                return [
                    'reason1' => $reportEntry->getRejectReason1Mont() ?? 0,
                    'reason2' => $reportEntry->getRejectReason2Mont() ?? 0,
                    'reason3' => $reportEntry->getRejectReason3Mont() ?? 0,
                    'reason4' => $reportEntry->getRejectReason4Mont() ?? 0,
                    'reason5' => $reportEntry->getRejectReason5Mont() ?? 0,
                    'reason6' => $reportEntry->getRejectReason6Mont() ?? 0,
                    'reason7' => $reportEntry->getRejectReason7Mont() ?? 0,
                    'reason8' => $reportEntry->getRejectReason8Mont() ?? 0,
                ];
            case 'tont':
                return [
                    'reason1' => $reportEntry->getRejectReason1Tont() ?? 0,
                    'reason2' => $reportEntry->getRejectReason2Tont() ?? 0,
                    'reason3' => $reportEntry->getRejectReason3Tont() ?? 0,
                    'reason4' => $reportEntry->getRejectReason4Tont() ?? 0,
                    'reason5' => $reportEntry->getRejectReason5Tont() ?? 0,
                    'reason6' => $reportEntry->getRejectReason6Tont() ?? 0,
                    'reason7' => $reportEntry->getRejectReason7Tont() ?? 0,
                    'reason8' => $reportEntry->getRejectReason8Tont() ?? 0,
                ];
            case 'cvs':
                return [
                    'reason1' => $reportEntry->getRejectReason1Cvs() ?? 0,
                    'reason2' => $reportEntry->getRejectReason2Cvs() ?? 0,
                    'reason3' => $reportEntry->getRejectReason3Cvs() ?? 0,
                    'reason4' => $reportEntry->getRejectReason4Cvs() ?? 0,
                    'reason5' => $reportEntry->getRejectReason5Cvs() ?? 0,
                    'reason6' => $reportEntry->getRejectReason6Cvs() ?? 0,
                    'reason7' => $reportEntry->getRejectReason7Cvs() ?? 0,
                    'reason8' => $reportEntry->getRejectReason8Cvs() ?? 0,
                ];
            case 'toft':
                return [
                    'reason1' => $reportEntry->getRejectReason1Toft() ?? 0,
                    'reason2' => $reportEntry->getRejectReason2Toft() ?? 0,
                    'reason3' => $reportEntry->getRejectReason3Toft() ?? 0,
                    'reason4' => $reportEntry->getRejectReason4Toft() ?? 0,
                    'reason5' => $reportEntry->getRejectReason5Toft() ?? 0,
                    'reason6' => $reportEntry->getRejectReason6Toft() ?? 0,
                    'reason7' => $reportEntry->getRejectReason7Toft() ?? 0,
                    'reason8' => $reportEntry->getRejectReason8Toft() ?? 0,
                ];
            case 'ecomm':
                return [
                    'reason1' => $reportEntry->getRejectReason1Ecomm() ?? 0,
                    'reason2' => $reportEntry->getRejectReason2Ecomm() ?? 0,
                    'reason3' => $reportEntry->getRejectReason3Ecomm() ?? 0,
                    'reason4' => $reportEntry->getRejectReason4Ecomm() ?? 0,
                    'reason5' => $reportEntry->getRejectReason5Ecomm() ?? 0,
                    'reason6' => $reportEntry->getRejectReason6Ecomm() ?? 0,
                    'reason7' => $reportEntry->getRejectReason7Ecomm() ?? 0,
                    'reason8' => $reportEntry->getRejectReason8Ecomm() ?? 0,
                ];
            default:
                return null;
        }
    }
    
    /**
     * Update week data in specific column of the worksheet
     */
    private function updateWeekDataInSheet(
        \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $worksheet, 
        string $column, 
        array $weekData, 
        SymfonyStyle $io
    ): void {
        // Find and update existing data rows
        $updatedRows = 0;
        
        // Look for "Total Participation" row (usually around row 8)
        for ($row = 6; $row <= 15; $row++) {
            $cellValue = trim($worksheet->getCell('B' . $row)->getCalculatedValue());
            if (stripos($cellValue, 'total participation') !== false || 
                stripos($cellValue, 'total') !== false) {
                $worksheet->setCellValue($column . $row, $weekData['total']);
                $updatedRows++;
                break;
            }
        }
        
        // Look for "Valid" row (usually around row 9)
        for ($row = 6; $row <= 15; $row++) {
            $cellValue = trim($worksheet->getCell('B' . $row)->getCalculatedValue());
            if (stripos($cellValue, 'valid') !== false && stripos($cellValue, 'invalid') === false) {
                $worksheet->setCellValue($column . $row, $weekData['valid']);
                $updatedRows++;
                break;
            }
        }
        
        // Look for "Invalid" row (usually around row 10)
        for ($row = 6; $row <= 15; $row++) {
            $cellValue = trim($worksheet->getCell('B' . $row)->getCalculatedValue());
            if (stripos($cellValue, 'invalid') !== false) {
                $worksheet->setCellValue($column . $row, $weekData['invalid']);
                $updatedRows++;
                break;
            }
        }
        
        // Look for "Pending" row (usually around row 11)
        for ($row = 6; $row <= 15; $row++) {
            $cellValue = trim($worksheet->getCell('B' . $row)->getCalculatedValue());
            if (stripos($cellValue, 'pending') !== false) {
                $worksheet->setCellValue($column . $row, $weekData['pending']);
                $updatedRows++;
                break;
            }
        }
    }

    /**
     * Update calculated columns F (% WTD vs 2024) and H (WTD vs Benchmark)
     */
    private function updateCalculatedColumns(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $worksheet, SymfonyStyle $io): void
    {
        $io->text("    🧮 Calculating Column F (% WTD vs 2024) and Column H (WTD vs Benchmark)");
        
        // Find data rows and calculate values
        for ($row = 6; $row <= 15; $row++) {
            $cellValue = trim($worksheet->getCell('B' . $row)->getCalculatedValue());
            
            if (empty($cellValue)) continue;
            
            // Get values from columns C (Benchmark), D (2024), E (2025)
            $benchmarkValue = $worksheet->getCell('C' . $row)->getCalculatedValue();
            $value2024 = $worksheet->getCell('D' . $row)->getCalculatedValue();
            $value2025 = $worksheet->getCell('E' . $row)->getCalculatedValue();
            
            // Skip if essential values are missing or not numeric
            if (!is_numeric($value2024) || !is_numeric($value2025)) {
                continue;
            }
            
            // Calculate Column F: % WTD vs 2024
            if ($value2024 > 0) {
                // Don't multiply by 100 - Excel percentage format will handle this
                $percentageChange = ($value2025 / $value2024) - 1;
                $worksheet->setCellValue('F' . $row, round($percentageChange, 4));
                // Set percentage format
                $worksheet->getStyle('F' . $row)->getNumberFormat()->setFormatCode('0.00%');
            } else {
                $worksheet->setCellValue('F' . $row, 0);
                $worksheet->getStyle('F' . $row)->getNumberFormat()->setFormatCode('0.00%');
            }
            
            // Calculate Column H: WTD vs Benchmark (difference)
            if (is_numeric($benchmarkValue)) {
                $benchmarkDiff = $value2025 - $benchmarkValue;
                $worksheet->setCellValue('H' . $row, $benchmarkDiff);
            } else {
                $worksheet->setCellValue('H' . $row, $value2025); // If no benchmark, show current value
            }
            
            $metricType = $cellValue;
            $io->text("      ✓ {$metricType}: F={$worksheet->getCell('F' . $row)->getCalculatedValue()}, H={$worksheet->getCell('H' . $row)->getCalculatedValue()}");
        }
    }

    /**
     * Set future weeks (beyond weekNumber) to 0 instead of #REF!
     */
    private function setFutureWeeksToZero(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $worksheet, int $weekNumber, SymfonyStyle $io): void
    {
        $io->text("    🔄 Setting future weeks (beyond week {$weekNumber}) to 0");
        
        // Week column mapping for 2025 (odd columns are 2025 data)
        $weekColumnMap = [
            1 => 'K',   // Week 1 (2025)
            2 => 'M',   // Week 2 (2025)
            3 => 'O',   // Week 3 (2025)
            4 => 'Q',   // Week 4 (2025)
            5 => 'S',   // Week 5 (2025)
            6 => 'U',   // Week 6 (2025)
            7 => 'W',   // Week 7 (2025)
            8 => 'Y',   // Week 8 (2025)
            9 => 'AA',  // Week 9 (2025)
            10 => 'AC', // Week 10 (2025)
        ];
        
        // Set future weeks to 0
        for ($week = $weekNumber + 1; $week <= 10; $week++) {
            $column = $weekColumnMap[$week] ?? null;
            if (!$column) continue;
            
            // Find data rows and set to 0
            for ($row = 6; $row <= 15; $row++) {
                $cellValue = trim($worksheet->getCell('B' . $row)->getCalculatedValue());
                
                if (stripos($cellValue, 'total participation') !== false || 
                    stripos($cellValue, 'total') !== false ||
                    stripos($cellValue, 'valid') !== false ||
                    stripos($cellValue, 'invalid') !== false ||
                    stripos($cellValue, 'pending') !== false) {
                    
                    $worksheet->setCellValue($column . $row, 0);
                }
            }
            
            $io->text("      ✓ Week {$week} (Column {$column}) set to 0");
        }
    }

    /**
     * Remove formulas from worksheet while preserving formatting
     */
    private function removeFormulasKeepFormat(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $worksheet, SymfonyStyle $io): void
    {
        $io->info("Removing formulas while preserving format...");
        
        $formulaCount = 0;
        $processedCount = 0;
        
        // Use cell iterator to only process existing cells
        try {
            $cellIterator = $worksheet->getRowIterator();
            
            foreach ($cellIterator as $row) {
                $cellIterator = $row->getCellIterator();
                $cellIterator->setIterateOnlyExistingCells(true); // Only iterate over existing cells
                
                foreach ($cellIterator as $cell) {
                    $processedCount++;
                    
                    // Show progress every 5000 cells for existing cells
                    if ($processedCount % 5000 == 0) {
                        $io->info("Processed {$processedCount} existing cells...");
                    }
                    
                    // Skip 2025 week data columns (K, M, O, Q, S, U, W, Y, AA, AC) to preserve data insertion points
                    $column = $cell->getColumn();
                    $weekColumns = ['K', 'M', 'O', 'Q', 'S', 'U', 'W', 'Y', 'AA', 'AC'];
                    if (in_array($column, $weekColumns)) {
                        continue; // Skip these columns during formula removal
                    }
                    
                    if ($cell->isFormula()) {
                        $formulaCount++;
                        
                        try {
                            // Try to get calculated value
                            $calculatedValue = $cell->getCalculatedValue();
                            
                            // Set the value directly, removing the formula
                            $cell->setValue($calculatedValue);
                            
                        } catch (\PhpOffice\PhpSpreadsheet\Calculation\Exception $calcException) {
                            // Calculation error - set to 0 for numeric or empty for text
                            $cell->setValue(0);
                        } catch (\Exception $e) {
                            // Any other error - set to empty
                            $cell->setValue('');
                        }
                    }
                }
            }
            
        } catch (\Exception $e) {
            $io->warning("Error using cell iterator, falling back to manual method: " . $e->getMessage());
            
            // Fallback to manual method but with a reasonable limit
            $highestRow = min($worksheet->getHighestRow(), 1000); // Limit to 1000 rows
            $highestColumn = $worksheet->getHighestColumn();
            
            $io->info("Processing range A1:{$highestColumn}{$highestRow} (limited)");
            
            for ($row = 1; $row <= $highestRow; $row++) {
                for ($col = 'A'; $col <= $highestColumn; $col++) {
                    $processedCount++;
                    
                    try {
                        $cell = $worksheet->getCell($col . $row, false);
                        
                        if ($cell && $cell->isFormula()) {
                            $formulaCount++;
                            
                            try {
                                $calculatedValue = $cell->getCalculatedValue();
                                $cell->setValue($calculatedValue);
                            } catch (\Exception $e) {
                                $cell->setValue('');
                            }
                        }
                    } catch (\Exception $e) {
                        continue;
                    }
                }
            }
        }
        
        $io->info("Formulas removed successfully - converted {$formulaCount} formulas to values from {$processedCount} processed cells");
    }

    /**
     * Populate data sheet with ReportEntry and ReportByState data
     */
    private function populateDataSheet(
        \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $worksheet, 
        ?ReportEntry $reportEntry, 
        array $reportByStateData,
        int $weekNumber, 
        SymfonyStyle $io
    ): void {
        $io->text("  📊 Populating data sheet...");
        
        $sheetName = $worksheet->getTitle();
        
        if ($sheetName === 'data') {
            $this->populateMainDataSheet($worksheet, $reportByStateData, $weekNumber, $io);
        } elseif ($sheetName === 'data for pivot') {
            $this->populatePivotDataSheet($worksheet, $reportByStateData, $weekNumber, $io);
        } else {
            $io->text("    ⚠️  Unknown data sheet format: {$sheetName}");
        }
    }
    
    /**
     * Populate the main 'data' sheet with channel/week structure
     */
    private function populateMainDataSheet(
        \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $worksheet,
        array $reportByStateData,
        int $weekNumber,
        SymfonyStyle $io
    ): void {
        $io->text("    📈 Populating main data sheet with weekly channel data");
        
        if (empty($reportByStateData)) {
            $io->text("    ⚠️  No ReportByState data to populate");
            return;
        }
        
        // Group data by channel and status
        $channelData = [];
        foreach ($reportByStateData as $report) {
            $channel = $report->getChannel();
            
            if (!isset($channelData[$channel])) {
                $channelData[$channel] = [
                    'valid' => 0,
                    'invalid' => 0,
                    'pending' => 0,
                    'total' => 0
                ];
            }
            
            // For now, put all entries as 'valid' - adjust based on your ReportByState entity
            $entries = $report->getEntries() ?? 0;
            $channelData[$channel]['valid'] += $entries;
            $channelData[$channel]['total'] += $entries;
            
            // If you have status-specific methods in ReportByState, use them:
            // $status = $report->getStatus(); // assuming you have this method
            // if ($status === 'valid') {
            //     $channelData[$channel]['valid'] += $entries;
            // } elseif ($status === 'invalid') {
            //     $channelData[$channel]['invalid'] += $entries;
            // } else {
            //     $channelData[$channel]['pending'] += $entries;
            // }
        }
        
        // Calculate the correct week column (C=Week1, D=Week2, etc.)
        $weekColumn = chr(ord('C') + $weekNumber - 1); // Week 1 = C, Week 2 = D, etc.
        
        $io->text("    📅 Inserting data into week column: {$weekColumn} (Week {$weekNumber})");
        
        // Find existing rows and update them, or create new ones
        $existingRows = [];
        
        // Scan existing rows to find channel/status combinations
        for ($row = 2; $row <= 50; $row++) {
            $channelCell = trim($worksheet->getCell('A' . $row)->getCalculatedValue());
            $statusCell = trim($worksheet->getCell('B' . $row)->getCalculatedValue());
            
            if (!empty($channelCell) && !empty($statusCell)) {
                $key = strtoupper($channelCell) . '_' . strtoupper($statusCell);
                $existingRows[$key] = $row;
            }
        }
        
        $updatedRows = 0;
        
        // Update existing rows with new data
        foreach ($channelData as $channel => $data) {
            foreach (['valid', 'invalid', 'pending'] as $status) {
                $key = strtoupper($channel) . '_' . strtoupper($status);
                
                if (isset($existingRows[$key])) {
                    // Update existing row
                    $row = $existingRows[$key];
                    $worksheet->setCellValue($weekColumn . $row, $data[$status]);
                    $updatedRows++;
                    $io->text("    ✓ Updated {$channel}/{$status} in row {$row}, column {$weekColumn}: {$data[$status]}");
                }
            }
        }
        
        // If no existing rows were found, create new ones
        if ($updatedRows === 0) {
            $io->text("    📝 No existing rows found, creating new data rows...");
            
            $currentRow = 2;
            // Find the first empty row
            while ($currentRow <= 100) {
                $channelCell = trim($worksheet->getCell('A' . $currentRow)->getCalculatedValue());
                if (empty($channelCell)) {
                    break;
                }
                $currentRow++;
            }
            
            foreach ($channelData as $channel => $data) {
                // Create rows for each status
                foreach (['valid', 'invalid', 'pending'] as $status) {
                    $worksheet->setCellValue('A' . $currentRow, $channel);
                    $worksheet->setCellValue('B' . $currentRow, ucfirst($status));
                    $worksheet->setCellValue($weekColumn . $currentRow, $data[$status]);
                    $io->text("    ✓ Created {$channel}/{$status} in row {$currentRow}, column {$weekColumn}: {$data[$status]}");
                    $currentRow++;
                }
            }
        }
        
        $io->text("    ✅ Populated " . count($channelData) . " channels in main data sheet (Week {$weekNumber}, Column {$weekColumn})");
    }
    
    /**
     * Populate the 'data for pivot' sheet with detailed records
     */
    private function populatePivotDataSheet(
        \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $worksheet,
        array $reportByStateData,
        int $weekNumber,
        SymfonyStyle $io
    ): void {
        $io->text("    📊 Populating pivot data sheet with detailed records");
        
        // Clear existing data (keep headers in row 1)
        $startRow = 2;
        for ($row = $startRow; $row <= $startRow + 100; $row++) {
            for ($col = 'A'; $col <= 'D'; $col++) {
                $worksheet->setCellValue($col . $row, '');
            }
        }
        
        // Populate with ReportByState data
        $currentRow = $startRow;
        foreach ($reportByStateData as $report) {
            $worksheet->setCellValue('A' . $currentRow, 'Y25'); // Year
            $worksheet->setCellValue('B' . $currentRow, 'W' . $weekNumber); // Week
            $worksheet->setCellValue('C' . $currentRow, $report->getChannel()); // Channel
            $worksheet->setCellValue('D' . $currentRow, $report->getEntries() ?? 0); // Entry count
            $currentRow++;
        }
        
        $io->text("    ✓ Populated " . count($reportByStateData) . " records in pivot data sheet");
    }

    /**
     * Populate state sheet with ReportByState data
     */
    private function populateStateSheet(
        \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $worksheet, 
        array $reportByStateData, 
        int $weekNumber, 
        SymfonyStyle $io
    ): void {
        $io->info("Populating state sheet...");
        
        if (empty($reportByStateData)) {
            $io->warning("No ReportByState data to populate");
            return;
        }

        // This is a placeholder - you'll need to map your data to specific cells
        // based on your template structure
        
        // Example: Add headers
        $worksheet->setCellValue('A1', 'Week Number');
        $worksheet->setCellValue('B1', 'State');
        $worksheet->setCellValue('C1', 'City');
        $worksheet->setCellValue('D1', 'Channel');
        $worksheet->setCellValue('E1', 'Entries');
        
        // Add data rows
        $row = 2;
        foreach ($reportByStateData as $stateData) {
            $worksheet->setCellValue('A' . $row, $stateData->getWeekNumber());
            $worksheet->setCellValue('B' . $row, $stateData->getState());
            $worksheet->setCellValue('C' . $row, $stateData->getCity());
            $worksheet->setCellValue('D' . $row, $stateData->getChannel());
            $worksheet->setCellValue('E' . $row, $stateData->getEntries());
            $row++;
        }
        
        $io->info("State sheet populated with " . count($reportByStateData) . " records");
    }
}