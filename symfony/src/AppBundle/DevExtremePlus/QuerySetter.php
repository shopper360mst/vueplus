<?php

/**** This class is written to replace DBSet which is not very compatible with Symfony ******/
namespace App\AppBundle\DevExtremePlus;
class QuerySetter {
    private static $SELECT_OP = "SELECT";
    private static $FROM_OP = "FROM";
    private static $WHERE_OP = "WHERE";
    private static $ORDER_OP = "ORDER BY";
    private static $GROUP_OP = "GROUP BY";
    private static $ALL_FIELDS = "*";
    private static $LIMIT_OP = "LIMIT";
    private static $INSERT_OP = "INSERT INTO";
    private static $VALUES_OP = "VALUES";
    private static $UPDATE_OP = "UPDATE";
    private static $SET_OP = "SET";
    private static $DELETE_OP = "DELETE";
    private static $MAX_ROW_INDEX = 2147483647;
    private $dbTableName;
    private $tableNameIndex = 0;
    private $lastWrappedTableName;
    private $resultQuery;
    private $lastError;
    private $conn;
    private $groupSettings;
    public function __construct($conn, $sql, $table) {
        $this->dbTableName = $table;
        $this->resultQuery = $sql;
        $this->conn = $conn;
    }
    public function GetLastError() {
        return $this->lastError;
    }
    private function _WrapQuery() {
        $this->tableNameIndex++;
        $this->lastWrappedTableName = "{$this->dbTableName}_{$this->tableNameIndex}";
        $this->resultQuery = sprintf("%s %s %s (%s) %s %s",
                                      self::$SELECT_OP,
                                      self::$ALL_FIELDS,
                                      self::$FROM_OP,
                                      $this->resultQuery,
                                      AggregateHelper::AS_OP,
                                      $this->lastWrappedTableName);
    }
    private function _PrepareQueryForLastOperator($operator) {
        $operator = trim($operator);
        $lastOperatorPos = strrpos($this->resultQuery, " ".$operator." ");
        if ($lastOperatorPos !== false) {
            $lastBracketPos = strrpos($this->resultQuery, ")");
            if (($lastBracketPos !== false && $lastOperatorPos > $lastBracketPos) || ($lastBracketPos === false)) {
                $this->_WrapQuery();
            }
        }
    }
    public function Select($expression) {
        Utils::EscapeExpressionValues($expression);
        $this->_SelectImpl($expression);
        return $this;
    }
    private function _SelectImpl($expression, $needQuotes = true) {
        if (isset($expression)) {
            $fields = "";
            if (is_string($expression)) {
                $expression = explode(",", $expression);
            }
            if (is_array($expression)) {
                foreach ($expression as $field) {
                    $fields .= (strlen($fields) ? ", " : "").($needQuotes ? Utils::QuoteStringValue(trim($field)) : trim($field));
                }
            }
            if (strlen($fields)) {
                $allFieldOperatorPos = strpos($this->resultQuery, self::$ALL_FIELDS);
                if ($allFieldOperatorPos == 7) {
                    $this->resultQuery = substr_replace($this->resultQuery, $fields, 7, strlen(self::$ALL_FIELDS));
                }
                else {
                    $this->_WrapQuery();
                    $this->_SelectImpl($expression);
                }
            }
        }
    }

    public static function GetParseParams($params, $assoc = false) {
        $result = NULL;
        if (is_array($params)) {
            $result = array();
            foreach ($params as $key => $value) {
                $result[$key] = json_decode($params[$key], $assoc);
                if ($result[$key] === NULL) {
                    $result[$key] = $params[$key];
                }
            }
        } else {
            $result = $params;
        }
        return $result;
    }

    public static function GetParamsFromInput() {
        $result = NULL;
        $content = file_get_contents("php://input");
        if ($content !== false) {
            $params = array();
            parse_str($content, $params);
            $result = QuerySetter::GetParseParams($params, true); 
        } 
        return $result;
    }


    public function Filter($expression) {
        Utils::EscapeExpressionValues($expression);
        
        if (isset($expression) && is_array($expression)) {
            $result = FilterHelper::GetSqlExprByArray($expression);
            if (strlen($result)) {
                $this->_PrepareQueryForLastOperator(self::$WHERE_OP);
                $this->resultQuery .= sprintf(" %s %s",
                                               self::$WHERE_OP,
                                               $result);
            }
        }
        return $this;
    }
    public function Sort($expression) {
        Utils::EscapeExpressionValues($expression);
        if (isset($expression)) {
            $result = "";
            if (is_string($expression)) {
                $result = trim($expression);
            }
            if (is_array($expression)) {
                $fieldSet =  AggregateHelper::GetFieldSetBySelectors($expression);
                $result = $fieldSet["sort"];
            }
            if (strlen($result)) {
                $this->_PrepareQueryForLastOperator(self::$ORDER_OP);
                $this->resultQuery .= sprintf(" %s %s",
                                               self::$ORDER_OP,
                                               $result);
            }
        }
        return $this;
    }
    public function SkipTake($skip, $take) {
        $skip = (!isset($skip) || !is_int($skip) ? 0 : $skip);
        $take = (!isset($take) || !is_int($take) ? self::$MAX_ROW_INDEX : $take);
        if ($skip != 0 || $take != 0) {
            $this->_PrepareQueryForLastOperator(self::$LIMIT_OP);
            $this->resultQuery .= sprintf(" %s %0.0f, %0.0f",
                                           self::$LIMIT_OP,
                                           $skip,
                                           $take);
        }
        return $this;
    }
    private function _CreateGroupCountQuery($firstGroupField, $skip = NULL, $take = NULL) {
        $groupCount = $this->groupSettings["groupCount"];
        $lastGroupExpanded = $this->groupSettings["lastGroupExpanded"];
        if (!$lastGroupExpanded) {
            if ($groupCount === 2) {
                $this->groupSettings["groupItemCountQuery"] = sprintf("%s COUNT(1) %s (%s) AS %s_%d",
                                                                        self::$SELECT_OP,
                                                                        self::$FROM_OP,
                                                                        $this->resultQuery,
                                                                        $this->dbTableName,
                                                                        $this->tableNameIndex + 1);
                if (isset($skip) || isset($take)) {
                    $this->SkipTake($skip, $take);
                }
            }
        }
        else {
            $groupQuery = sprintf("%s COUNT(1) %s %s %s %s",
                                   self::$SELECT_OP,
                                   self::$FROM_OP,
                                   $this->dbTableName,
                                   self::$GROUP_OP,
                                   $firstGroupField);
            $this->groupSettings["groupItemCountQuery"] = sprintf("%s COUNT(1) %s (%s) AS %s_%d",
                                                                   self::$SELECT_OP,
                                                                   self::$FROM_OP,
                                                                   $groupQuery,
                                                                   $this->dbTableName,
                                                                   $this->tableNameIndex + 1);
            if (isset($skip) || isset($take)) {
                $this->groupSettings["skip"] = isset($skip) ? Utils::StringToNumber($skip) : 0;
                $this->groupSettings["take"] = isset($take) ? Utils::StringToNumber($take) : 0;
            }
        }
    }
    public function Group($expression, $groupSummary = NULL, $skip = NULL, $take = NULL) {
        Utils::EscapeExpressionValues($expression);
        Utils::EscapeExpressionValues($groupSummary);
        $this->groupSettings = NULL;
        if (isset($expression)) {
            $groupFields = "";
            $sortFields = "";
            $selectFields = "";
            $lastGroupExpanded = true;
            $groupCount = 0;
            if (is_string($expression)) {
                $selectFields = $sortFields = $groupFields = trim($expression);
                $groupCount = count(explode(",", $expression));
            }
            if (is_array($expression)) {
                $groupCount = count($expression);
                $fieldSet = AggregateHelper::GetFieldSetBySelectors($expression);
                $groupFields = $fieldSet["group"];
                $selectFields = $fieldSet["select"];
                $sortFields = $fieldSet["sort"];
                $lastGroupExpanded = AggregateHelper::IsLastGroupExpanded($expression);
            }
            if ($groupCount > 0) {
                if (!$lastGroupExpanded) {
                    $groupSummaryData = isset($groupSummary) && is_array($groupSummary) ? AggregateHelper::GetSummaryInfo($groupSummary) : NULL;
                    $selectExpression = sprintf("%s, %s(1)%s",
                                                strlen($selectFields) ? $selectFields : $groupFields,
                                                AggregateHelper::COUNT_OP,
                                                (isset($groupSummaryData) && isset($groupSummaryData["fields"]) && strlen($groupSummaryData["fields"]) ?
                                                 ", ".$groupSummaryData["fields"] : ""));
                    $groupCount++;
                    $this->_WrapQuery();
                    $this->_SelectImpl($selectExpression, false);
                    $this->resultQuery .= sprintf(" %s %s",
                                                   self::$GROUP_OP,
                                                   $groupFields);
                    $this->Sort($sortFields);
                }
                else {
                    $this->_WrapQuery();
                    $selectExpression = "{$selectFields}, {$this->lastWrappedTableName}.*";
                    $this->_SelectImpl($selectExpression, false);
                    $this->resultQuery .= sprintf(" %s %s",
                                                    self::$ORDER_OP,
                                                    $sortFields);
                }
                $this->groupSettings = array();
                $this->groupSettings["groupCount"] = $groupCount;
                $this->groupSettings["lastGroupExpanded"] = $lastGroupExpanded;
                $this->groupSettings["summaryTypes"] = !$lastGroupExpanded ? $groupSummaryData["summaryTypes"] : NULL;
                $firstGroupField = explode(",", $groupFields)[0];
                $this->_CreateGroupCountQuery($firstGroupField, $skip, $take);
            }
        }
        return $this;
    }
    //TODO : NOT TESTED.
    public function GetTotalSummary($expression, $filterExpression = NULL) {
        Utils::EscapeExpressionValues($expression);
        Utils::EscapeExpressionValues($filterExpression);
        $result = NULL;
        if (isset($expression) && is_array($expression)) {
            $summaryInfo = AggregateHelper::GetSummaryInfo($expression);
            $fields = $summaryInfo["fields"];
            if (strlen($fields) > 0) {
                $filter = "";
                if (isset($filterExpression)) {
                    if (is_string($filterExpression)) {
                        $filter = trim($filterExpression);
                    }
                    if (is_array($filterExpression)) {
                        $filter = FilterHelper::GetSqlExprByArray($filterExpression);
                    }
                }
                $totalSummaryQuery = sprintf("%s %s %s %s %s",
                                              self::$SELECT_OP,
                                              $fields,
                                              self::$FROM_OP,
                                              $this->dbTableName,
                                              strlen($filter) > 0 ? self::$WHERE_OP." ".$filter : $filter);
                $this->lastError = NULL;
                
                $stmt = $this->conn->prepare($totalSummaryQuery);
                $query_result = $stmt->executeQuery();
                
                if (!$$query_result) {
                    $this->lastError = "Error in Total Summary";
                }
                else if ($query_result->num_rows > 0) {
                    $result =  $query_result->fetchAllAssociative();        
                    foreach ($result as $i => $item) {
                        $result[$i] = Utils::StringToNumber($item);
                    }
                }
                if ($query_result !== false) {
                    $this->conn->close();
                }
            }
        }
        return $result;
    }
    //TODO : NOT TESTED.    
    public function GetGroupCount() {
        $result = 0;
        if ($this->conn && isset($this->groupSettings) && isset($this->groupSettings["groupItemCountQuery"])) {
            $this->lastError = NULL;

            $stmt = $this->conn->prepare($this->groupSettings["groupItemCountQuery"]);
            $query_result = $stmt->executeQuery();
            if (!$$query_result) {
                $this->lastError = "Error in Group Count";
            }
            else if ($query_result->rowCount() > 0) {
                $row = $query_result->fetchAllAssociative();            
                $result = Utils::StringToNumber($row[0]['COUNT(1)']);
            }
            if ($query_result !== false) {
                $this->conn->close();
            }
        }
        return $result;
    }
    public function GetCount() {
        $result = 0;
        $countQuery = sprintf("%s %s(1) %s (%s) %s %s_%d",
                                self::$SELECT_OP,
                                AggregateHelper::COUNT_OP,
                                self::$FROM_OP,
                                $this->resultQuery,
                                AggregateHelper::AS_OP,
                                $this->dbTableName,
                                $this->tableNameIndex + 1);
        $this->lastError = NULL;
        $stmt = $this->conn->prepare($countQuery);
        $query_result = $stmt->executeQuery();
        if (!$query_result) {
            $this->lastError = "Error in Count";
        }
        else if ($query_result->rowCount() > 0) {
            $row = $query_result->fetchAllAssociative();            
            $result = Utils::StringToNumber($row[0]['COUNT(1)']);
        }
        if ($query_result !== false) {
            $this->conn->close();
        }
        
        return $result;
    }
    public function AsArray() {
        $result = NULL;
        $this->lastError = NULL;
        $stmt = $this->conn->prepare($this->resultQuery);
        $query_result = $stmt->executeQuery();
        if (!$query_result) {
            $this->lastError = "Error in Array";
        }
        else {
            if (isset($this->groupSettings)) {
                $result = AggregateHelper::GetGroupedDataFromQuery($query_result, $this->groupSettings);
            }
            else {
                $result = $query_result->fetchAllAssociative();
            }
            $this->conn->close();
        }
        return $result;
    }
}