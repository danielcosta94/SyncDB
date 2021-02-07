<?php

require_once "config.php";
require_once "helpers.php";

/**
 * Prepare all row's data to be inserted into HT database tables
 *
 * @param array $row The row the be prepared to insertion
 */
function prepareRowData(array &$row)
{
    sanitizeDataQueried($row);
    convertAllDates($row);
    roundRawGrade($row);
}

/**
 * Insert data into tables
 *
 * @param $ht_connection  HT database connection
 * @param $moodle_query   Moodle query to be parsed
 *
 * @return int[]          Results with the number of rows inserted and not inserted
 */
function insertData($ht_connection, $moodle_query): array
{
    $inserted_rows = 0;
    $not_inserted_rows = 0;

    while ($row = $moodle_query->fetch_assoc()) {
        prepareRowData($row);
        encodeRowStringsToSqlObject($row);

        foreach (getReferenceActions($row["fullname"]) as $ref_action) {
            // Insert new record into "TB_Avaliacao" table"
            $sql = "INSERT INTO TB_Avaliacao (email_formando, codigo_curso, ref_accao, modulo, parm_avaliacao, data, nota_final) VALUES (?, ?, ?, ?, ?, ?, ?)";
            $params = [$row["email"], $row["course_code"], $ref_action, $row["module"], $row["param_evaluation"], $row['time_modified_evaluation'], $row["rawgrade"]];

            $stmt = sqlsrv_query($ht_connection, $sql, $params);
            $stmt ? $inserted_rows++ : $not_inserted_rows++;
            sqlsrv_free_stmt($stmt);

            // Insert new record into "TB_Enunciado" table"
            $sql = "INSERT INTO TB_Enunciado (pergunta, resposta, codigo_curso, ref_accao, epoca, modulo, data) VALUES (?, ?, ?, ?, ?, ?, ?)";
            $params = [$row["questiontext"], $row["answer"], $row["course_code"], $ref_action, getEvaluationTime($row["itemname"]), $row["module"], $row['time_modified_test']];

            $stmt = sqlsrv_query($ht_connection, $sql, $params);
            $stmt ? $inserted_rows++ : $not_inserted_rows++;
            sqlsrv_free_stmt($stmt);
        }
    }

    return ['inserted_rows' => $inserted_rows, 'not_inserted_rows' => $not_inserted_rows];
}

$moodle_connection = mysqli_connect(MOODLE_DB_HOST, MOODLE_DB_USERNAME, MOODLE_DB_PASSWORD, MOODLE_DB_SCHEMA, MOODLE_DB_PORT);
// we connect to localhost at port 3306
if ($moodle_connection) {
    $ht_server_name = HT_DB_HOST; //serverName\instanceName
    $connectionInfo = ["Database" => HT_DB_SCHEMA, "UID"=> HT_DB_USERNAME, "PWD" => HT_DB_PASSWORD];
    $ht_connection_info = ["Database" => HT_DB_SCHEMA];
    $ht_connection = sqlsrv_connect($ht_server_name, $ht_connection_info);

    if ($ht_connection) {
        $moodle_query = $moodle_connection->query(<<<SQL
SELECT u.email, c.shortname AS course_code, c.fullname, cs.name AS module, gi.itemmodule AS param_evaluation, gg.timemodified AS time_modified_evaluation, gg.rawgrade, q.questiontext, qa.answer, gi.itemname, gi.timemodified AS time_modified_test
FROM grade_grades gg
INNER JOIN grade_items gi ON gg.itemid = gi.id
INNER JOIN course c ON c.id = gi.courseid
INNER JOIN user u ON u.id = gg.userid
INNER JOIN course_sections cs ON cs.course = c.id
INNER JOIN questionnaire_response_text qrt ON cs.course = c.id
INNER JOIN question q ON qrt.question_id = q.id
INNER JOIN question_answers qa ON qrt.response_id = qa.id
SQL
            , MYSQLI_USE_RESULT);

        if ($moodle_query) {
            try {
                // Clear HT database tables
                truncateTables($ht_connection);

                // Insert records into HT database;
                $results = insertData($ht_connection, $moodle_query);

                // Print results of insertions
                echo "A total of {$results['inserted_rows']} were inserted and {$results['not_inserted_rows']} were not inserted";
            } catch (Exception $exception) {
                echo "Error Code: {$exception->getCode()}, Message: {$exception->getMessage()}";
            } finally {
                sqlsrv_close($ht_connection);
            }
        } else {
            echo "Connection could not be established:" . sqlsrv_errors() . PHP_EOL;
        }
    }

    // Close Moodle DB Connection
    mysqli_close($moodle_connection);
} else {
    echo "Could not connect to moodle database!!!" . PHP_EOL;
    echo "Debugging errno: " . mysqli_connect_errno() . PHP_EOL;
    echo "Debugging error: " . mysqli_connect_error() . PHP_EOL;
}