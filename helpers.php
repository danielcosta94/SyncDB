<?php

/**
 * @link https://stackoverflow.com/questions/12462218/insert-special-characters-from-php-to-microsoft-sql-server
 *
 * Encode a PHP string into into right sql encoding to insert in a database table
 *
 * @param string|null $str   String to be encoded
 *
 * @return false|string|null String encoded to the right collation
 */
function encodePhpStringToSqlObject(?string $str = null) {
    return $str != null ? iconv(SOURCE_ENCODING, DESTINY_ENCODING, $str) : null;
}

/**
 * Sanitize row's data that has been queried
 *
 * @param array|null $row The row to be sanitized
 */
function sanitizeDataQueried(?array &$row = null)
{
    if ($row != null) {
        $row['email'] = $row['email'] != null ? filter_var($row['email'], FILTER_SANITIZE_EMAIL) : null;
        $row['course_code'] = $row['course_code'] != null ? filter_var($row['course_code'], FILTER_SANITIZE_STRING) : null;
        $row['fullname'] = $row['fullname'] != null ? filter_var($row['fullname'], FILTER_SANITIZE_STRING) : null;
        $row['module'] = $row['module'] != null ? filter_var($row['module'], FILTER_SANITIZE_STRING) : null;
        $row['param_evaluation'] = $row['param_evaluation'] != null ? filter_var($row['param_evaluation'], FILTER_SANITIZE_STRING) : null;
        $row['time_modified_evaluation'] = $row['time_modified_evaluation'] != null ? filter_var($row['time_modified_evaluation'], FILTER_SANITIZE_NUMBER_INT) : null;
        $row['rawgrade'] = $row['rawgrade'] != null ? filter_var($row['rawgrade'], FILTER_SANITIZE_STRING) : null;
        $row['questiontext'] = $row['questiontext'] != null ? str_replace(["\t", "\n", "\r"], " ", html_entity_decode(filter_var($row['questiontext'], FILTER_SANITIZE_STRING), FILTER_SANITIZE_STRING)) : null;
        $row['answer'] = $row['answer'] != null ? filter_var($row['answer'], FILTER_SANITIZE_STRING) : null;
        $row['itemname'] = $row['itemname'] != null ? filter_var($row['itemname'], FILTER_SANITIZE_STRING) : null;
        $row['time_modified_test'] = $row['time_modified_test'] != null ? filter_var($row['time_modified_test'], FILTER_SANITIZE_NUMBER_INT) : null;
    }
}

/**
 * Truncates all tables in HT database
 *
 * @param $ht_connection HT database connection previously opened
 */
function truncateTables($ht_connection)
{
    $sql = "TRUNCATE TABLE TB_Avaliacao";
    sqlsrv_query($ht_connection, $sql);

    $sql = "TRUNCATE TABLE TB_Enunciado";
    sqlsrv_query($ht_connection, $sql);
}

/**
 * Parse absolute integer date since (1970-01-01 00:00:00) to the pretended format
 *
 * @param int $dateInteger        The input datetime in integer format
 * @param string $destiny_format  The destiny format to transform the datetime
 *
 * @return false|string           Datetime transformed into the pretended format
 */
function parseDateIntegerToOtherFormat(int $dateInteger = 0, $destiny_format = DATE_TO_CONVERT_FORMAT)
{
    return date($destiny_format, $dateInteger);
}

/**
 * Convert all row's dates into pretended format
 *
 * @param array $row The row's dates to have the date converted into the pretended format
 */
function convertAllDates(array &$row)
{
    if ($row["time_modified_evaluation"] != null) {
        $row["time_modified_evaluation"] = parseDateIntegerToOtherFormat($row["time_modified_evaluation"]);
    }

    if ($row["time_modified_test"] != null) {
        $row["time_modified_test"] = parseDateIntegerToOtherFormat($row["time_modified_test"]);
    }
}

/**
 * Round evaluation raw grade
 *
 * @param array $row The row with the raw grade
 */
function roundRawGrade(array &$row)
{
    if ($row["rawgrade"] != null) {
        // Cast to float and round to unit
        $row["rawgrade"] = round(floatval($row["rawgrade"]));
    }
}

/**
 * Get evaluation time of the evaluation
 *
 * @param string|null $item_name  The item name to be parsed
 *
 * @return string|null            The evaluation time of the item
 */
function getEvaluationTime(?string $item_name = null): ?string
{
    if ($item_name != null) {
        foreach (EVALUATION_TIMES as $EVALUATION_TIME) {
            if (substr_compare($EVALUATION_TIME, $item_name, 0, null, true)) {
                return $EVALUATION_TIME;
            }
        }
    }
    return null;
}

/**
 * Get all the reference actions
 *
 * @param string|null $fullname Full name to be parse
 *
 * @return array                The list with all the reference actions
 */
function getReferenceActions(?string $fullname = null): array
{
    $ref_actions = [null];

    if ($fullname != null) {
        // Split by "||" to extract evaluation reference
        $full_name = mb_split("(\|\|) | (\/\/)", $fullname);

        if (count($full_name) > 1) {
            // Split by "/" to extract evaluation reference
            $ref_actions = mb_split("\/", $full_name[0]);

            for ($i = 0; $i < count($ref_actions); $i++) {
                $ref_actions[$i] = trim($ref_actions[$i]);
            }
        }
    }

    return $ref_actions;
}

/**
 * Get all needed values with the right SQL encoding
 *
 * @param array $row  Row with all the needed data to be encoded
 */
function encodeRowStringsToSqlObject(array &$row)
{
    $row['course_code'] = encodePhpStringToSqlObject($row['course_code']);
    $row['module'] = encodePhpStringToSqlObject($row['module']);
    $row['questiontext'] = encodePhpStringToSqlObject($row['questiontext']);
    $row['answer'] = encodePhpStringToSqlObject($row['answer']);
}