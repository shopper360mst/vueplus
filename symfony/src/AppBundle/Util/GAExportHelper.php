<?php 
/**
 *
 * A GAExportHelper API Interface Class.
 * No database required.
 * 
 */
namespace App\AppBundle\Util;

use Google\Analytics\Data\V1beta\Client\BetaAnalyticsDataClient;
use Google\Analytics\Data\V1beta\DateRange;
use Google\Analytics\Data\V1beta\Dimension;
use Google\Analytics\Data\V1beta\Metric;
use Google\Analytics\Data\V1beta\OrderBy;
use Google\Analytics\Data\V1beta\RunRealtimeReportRequest;
use Google\Analytics\Data\V1beta\RunReportRequest;


/**
 * Data Analytics API v1
 */
class GAExportHelper {
    public function __construct($secretKey, $propertyId) {
      $this->SECRETKEY = $secretKey;
      $this->PROPERTY_ID = intval($propertyId);
      putenv("GOOGLE_APPLICATION_CREDENTIALS=$this->SECRETKEY");
    }

    public function getWeeklyReport()  {
      $report_arr1 = [];
      $report_arr2 = [];
      $report_arr3 = [];

      $client = new BetaAnalyticsDataClient();
      $activePagesVsUserRequest = (new RunReportRequest())
        ->setProperty('properties/' . $this->PROPERTY_ID)
        ->setDateRanges([
            new DateRange([
                'start_date' => '7daysAgo',
                'end_date' => 'today',
            ]),
        ])
        ->setDimensions([new Dimension([
                'name' => 'unifiedScreenName',
            ]),
        ])
        
        ->setMetrics([new Metric([
                'name' => 'activeUsers',
            ])
        ]);

  
      $respPagesVsUserRequest = $client->runReport($activePagesVsUserRequest);
      foreach ($respPagesVsUserRequest->getRows() as $row) {
        array_push(
          $report_arr1, array(
              'label' => 'Page vs Active Users',
              'dimension' => $row->getDimensionValues()[0]->getValue(),
              'metric' => $row->getMetricValues()[0]->getValue() 
          )
        );
      }
      
      return [ 
        'weekly_reports' => [
            $report_arr1, 
        ]
      ];
    }

    public function getRealTimeReport()  {
        // Using a default constructor instructs the client to use the credentials
        // specified in GOOGLE_APPLICATION_CREDENTIALS environment variable.
        $realtime_report_arr1 = [];
        $realtime_report_arr2 = [];
        $realtime_report_arr3 = [];
        $realtime_report_arr4 = [];
                
        try {
            $client = new BetaAnalyticsDataClient();
         

            $activeCountryVsUserRequest = (new RunRealtimeReportRequest())
                ->setProperty('properties/' . $this->PROPERTY_ID)
                ->setDimensions([new Dimension([
                        'name' => 'country',
                    ]),
                ])
                ->setMetrics([new Metric([
                        'name' => 'activeUsers',
                    ])
                ]);
            $respCountryVsUser = $client->runRealtimeReport($activeCountryVsUserRequest);
            
            $activePagesVsUserRequest = (new RunRealtimeReportRequest())
                ->setProperty('properties/' . $this->PROPERTY_ID)
                ->setDimensions([new Dimension([
                        'name' => 'unifiedScreenName',
                    ]),
                ])
                ->setMetrics([new Metric([
                        'name' => 'activeUsers',
                    ])
                ]);
            $respPageNameVsUser = $client->runRealtimeReport($activePagesVsUserRequest);

            $activeOSVsUserRequest = (new RunRealtimeReportRequest())
                ->setProperty('properties/' . $this->PROPERTY_ID)
                ->setDimensions([new Dimension([
                        'name' => 'deviceCategory',
                    ]),
                ])
                ->setMetrics([new Metric([
                        'name' => 'activeUsers',
                    ])
                ]);
            $respOSVsUser = $client->runRealtimeReport($activeOSVsUserRequest);

            $totalActiveVsUserRequest = (new RunRealtimeReportRequest())
              ->setProperty('properties/' . $this->PROPERTY_ID)
              ->setDimensions([new Dimension([
                      'name' => 'date',
                  ]),
              ])
              ->setMetrics([new Metric([
                      'name' => 'activeUsers',
                  ])
              ]);
            $respTotalActiveVsUser = $client->runRealtimeReport($totalActiveVsUserRequest);

            foreach ($respCountryVsUser->getRows() as $row) {
              array_push(
                $realtime_report_arr1, array(
                    'label' => 'Countries vs Active Users ',
                    'dimension' => $row->getDimensionValues()[0]->getValue(),
                    'metric' => $row->getMetricValues()[0]->getValue() 
                )
              );
            }

            foreach ($respPageNameVsUser->getRows() as $row) {
              array_push(
                $realtime_report_arr2, array(
                    'label' => 'Page vs Active Users',
                    'dimension' => $row->getDimensionValues()[0]->getValue(),
                    'metric' => $row->getMetricValues()[0]->getValue() 
                )
            );
            }

            foreach ($respOSVsUser->getRows() as $row) {
              array_push(
                $realtime_report_arr3, array(
                    'label' => 'OS vs Active Users',
                    'dimension' => $row->getDimensionValues()[0]->getValue(),
                    'metric' => $row->getMetricValues()[0]->getValue() 
                )
              );
            }

            foreach ($respTotalActiveVsUser->getRows() as $row) {
              array_push(
                $realtime_report_arr4, array(
                    'label' => 'Date vs Active Users',
                    'dimension' => $row->getDimensionValues()[0]->getValue(),
                    'metric' => $row->getMetricValues()[0]->getValue() 
                )
              );
            }
        } catch(\Exception)  {

        }

        return [ 
          'realtime_reports' => [
              $realtime_report_arr1, 
              $realtime_report_arr2, 
              $realtime_report_arr3,
              $realtime_report_arr4
          ]
        ];
    }
}
?>