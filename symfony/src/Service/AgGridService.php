<?php 
namespace App\Service;
use Psr\Log\LoggerInterface;
use Doctrine\ORM\EntityManagerInterface;
/**
 *
 * A service for AgGrid.
 */
class AgGridService {
    public function buildSql($request,$table,$leftJoin = null)
    {
        if(isset($leftJoin)){
            $selectSql = $this->createSelectSql($request);
            $leftJoins = count($leftJoin['columns']) > 1 ? ', '.join(', ',$leftJoin['columns']) : ', '.$leftJoin['columns'];
            $fromSql = " FROM ".$table;
            $joinSql  =  " LEFT JOIN ".$leftJoin['table']." ON ".$leftJoin['table'].".".$leftJoin['main_column']." = ".$table.".".$leftJoin['join_column'];
            $whereSql = $this->whereSql($request);
            $groupBySql = $this->groupBySql($request);
            $orderBySql = $this->orderBySql($request);
            $limitSql = $this->createLimitSql($request);
    
            $SQL = $selectSql . $leftJoins . $fromSql . $joinSql . $whereSql . $groupBySql . $orderBySql . $limitSql;
            return $SQL;
        }else{
            $selectSql = $this->createSelectSql($request);
            $fromSql = " FROM ".$table;
            $whereSql = $this->whereSql($request);
            $groupBySql = $this->groupBySql($request);
            $orderBySql = $this->orderBySql($request);
            $limitSql = $this->createLimitSql($request);
    
            $SQL = $selectSql . $fromSql . $whereSql . $groupBySql . $orderBySql . $limitSql;
            return $SQL;
        }
    }

    public function createSelectSql($request)
    {
        $rowGroupCols = $request->rowGroupCols;
        $valueCols = $request->valueCols;
        $groupKeys = $request->groupKeys;
        $colsToSelect = [];
        if(isset($rowGroupCols)){
            if ($this->isDoingGrouping($rowGroupCols, $groupKeys)) {
                $rowGroupCol = $rowGroupCols[count($groupKeys)];
                array_push($colsToSelect, $rowGroupCol->field);
                
                foreach ($valueCols as $key => $value) {
                    array_push($colsToSelect, $value->aggFunc . '(' . $value->field . ') as ' . $value->field);
                }
    
                return "SELECT " . join(", ", $colsToSelect);
            }
        }
        return "SELECT *";
    }

    public function whereSql($request)
    {
        $rowGroupCols = $request->rowGroupCols;
        $groupKeys = $request->groupKeys;
        $filterModel = $request->filterModel;
        $whereParts = [];

        if (sizeof($groupKeys) > 0) {
            foreach ($groupKeys as $key => $value) {
                $colName = $rowGroupCols[$key]->field;
                array_push($whereParts, "{$colName} = '{$value}'");
            }
        }

        if (isset($filterModel)) {
            foreach ($filterModel as $key => $value) {
                $item = $key;
                if(!isset($value->conditions)){
                    switch ($value->filterType) {
                        case 'text':
                            array_push($whereParts , $this->textFilterMapper( $key, $value));
                            break;
                        case 'number':
                            array_push($whereParts , $this->numberFilterMapper( $key, $value));
                            break;
                        default:
                            error_log('unknown filter type: ' . $value->filterType);
                            break;
                    }
                }
                else{
                    for($i=0;$i<count($value->conditions);$i++){
                        switch ($value->conditions[$i]->filterType) {
                            case 'text':
                                array_push($whereParts , $this->textFilterMapper( $item, $value->conditions[$i]));
                                break;
                            case 'number':
                                array_push($whereParts , $this->numberFilterMapper( $item, $value->conditions[$i]));
                                break;
                            default:
                                error_log('unknown filter type: ' . $value->filterType);
                                break;
                        }
                    }
                }
            }
            
        }
        if (sizeof($whereParts) > 0) {
            return " WHERE " . join(' and ', $whereParts);
        } else {
            return "";
        }
    }

    private function textFilterMapper($key, $item) {
        switch ($item->type) {
            case 'equals':
                return $key . " = '" . $item->filter . "'";
            case 'notEqual':
                return $key . " != '" . $item->filter . "'";
            case 'contains':
                return $key . " LIKE '%" . $item->filter . "%'";
            case 'notContains':
                return $key . " NOT LIKE '%" . $item->filter . "%'";
            case 'startsWith':
                return $key . " LIKE '" . $item->filter . "%'";
            case 'endsWith':
                return $key . " LIKE '%" . $item->filter . "'";
            case 'blank':
                return $key . ' IS NULL or ' . $key . " = ''";
            case 'notBlank':
                return $key . ' IS NOT NULL and ' . $key . " != ''";
            default:
                error_log('unknown text filter type: ' . $item->type);
        }
    }
    
    private function numberFilterMapper($key, $item) {
        switch ($item->type) {
            case 'equals':
                return $key . ' = ' . $item->filter;
            case 'notEqual':
                return $key . ' != ' . $item->filter;
            case 'greaterThan':
                return $key . ' > ' . $item->filter;
            case 'greaterThanOrEqual':
                return $key . ' >= ' . $item->filter;
            case 'lessThan':
                return $key . ' < ' . $item->filter;
            case 'lessThanOrEqual':
                return $key . ' <= ' . $item->filter;
            case 'inRange':
                return '(' . $key . ' >= ' . $item->filter . ' and ' . $key . ' <= ' . $item->filterTo . ')';
            case 'blank':
                return $key . ' IS NULL';
            case 'notBlank':
                return $key . ' IS NOT NULL';
            default:
                error_log('unknown number filter type: ' . $item->type);
        }
    }
    

    public function groupBySql($request)
    {

        $rowGroupCols = $request->rowGroupCols;
        $groupKeys = $request->groupKeys;

        if ($this->isDoingGrouping($rowGroupCols, $groupKeys)) {
            $colsToGroupBy = [];

            $rowGroupCol = $rowGroupCols[sizeof($groupKeys)];
            array_push($colsToGroupBy, $rowGroupCol->field);

            return " GROUP BY " . join(", ", $colsToGroupBy);
        } else {
            // select all columns
            return "";
        }
    }

    public function orderBySql($request)
    {
        $sortModel = $request->sortModel;

        if ($sortModel) {
            $sortParts = [];

            foreach ($sortModel as $key => $value) {
                array_push($sortParts, $value->colId . " " . $value->sort);
            }

            if (sizeof($sortParts) > 0) {
                return " ORDER BY " . join(", ", $sortParts);
            } else {
                return '';
            }
        }
    }

    public function isDoingGrouping($rowGroupCols, $groupKeys)
    {
        // we are not doing grouping if at the lowest level. we are at the lowest level
        // if we are grouping by more columns than we have keys for (that means the user
        // has not expanded a lowest level group, OR we are not grouping at all).

        return sizeof($rowGroupCols) > sizeof($groupKeys);
    }

    public function createLimitSql($request)
    {
        $startRow = $request->startRow;
        $endRow = $request->endRow;
        $pageSize = ($endRow - $startRow) + 1;

        return " LIMIT {$pageSize} OFFSET {$startRow};";
    }

    public function getRowCount($request, $results)
    {
        if (is_null($results) || !isset($results) || sizeof($results) == 0) {
            // or return null
            return 0;
        }

        $currentLastRow = $request->startRow + sizeof($results);

        if ($currentLastRow <= $request->endRow) {
            return $currentLastRow;
        } else {
            return -1;
        }
    }

    public function cutResultsToPageSize($request, $results)
    {
        $pageSize = $request->endRow - $request->startRow;

        if ($results && (sizeof($results) > $pageSize)) {
            return array_splice($results, 0, $pageSize);
        } else {
            return $results;
        }
    }
}    
