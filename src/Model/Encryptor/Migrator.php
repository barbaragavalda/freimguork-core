<?php

namespace Core\Model\Encryptor;

use Core\Model\MySQL\PDO as MysqlPDO;

/**
 * Class Migrator
 * re-encrypts legacy TwoWay values to the current format, in place, one
 * table/column at a time. Idempotent (rows already migrated are skipped)
 * and safe to run against a live app (TwoWay::decrypt() already understands
 * both formats, so reads never break mid-migration).
 *
 * There is no OneWay equivalent: a one-way hash can't be decrypted, so
 * existing password-style hashes can only be upgraded lazily, at the next
 * successful OneWay::check() - see OneWay::needsRehash().
 */
class Migrator
{

    private const int BATCH_SIZE = 500;

    /**
     * @param MysqlPDO    $mysql
     * @param string      $table         e.g. 'appacman_user'
     * @param string      $idColumn      e.g. 'id_appacman_user'
     * @param string      $createdColumn e.g. 'created'
     * @param string      $field         column holding the TwoWay-encrypted value, e.g. 'email'
     * @param string|null $bidxColumn    companion blind-index column to (re)populate, e.g. 'email_bidx'
     *
     * @return int number of rows migrated
     */
    public static function reencryptTwoWayColumn(
        MysqlPDO $mysql,
        string $table,
        string $idColumn,
        string $createdColumn,
        string $field,
        ?string $bidxColumn = null
    ): int {
        $migrated = 0;
        $lastID   = 0;

        while (true) {
            $sql  = "
                SELECT `$idColumn` AS id, `$createdColumn` AS created, `$field` AS value
                FROM `$table`
                WHERE `$idColumn` > :lastID AND `$field` IS NOT NULL AND `$field` != ''
                ORDER BY `$idColumn` ASC
                LIMIT " . self::BATCH_SIZE . '
            ';
            $rows = $mysql->query($sql, array(
                'lastID' => array('value' => $lastID, 'type' => \PDO::PARAM_INT),
            ));

            if (!count($rows)) {
                break;
            }

            foreach ($rows as $row) {
                $lastID = $row['id'];

                if (!TwoWay::isLegacy($row['value'])) {
                    continue;
                }

                $context   = $row['id'] . '_' . $row['created'] . '_' . $field;
                $plaintext = TwoWay::decrypt($row['value'], $context);
                if ($plaintext === false) {
                    continue;
                }

                $set    = "`$field` = :value";
                $params = array(
                    'value' => array('value' => TwoWay::encrypt($plaintext, $context), 'type' => \PDO::PARAM_STR),
                    'id'    => array('value' => $row['id'], 'type' => \PDO::PARAM_INT),
                );
                if ($bidxColumn !== null) {
                    $set             .= ", `$bidxColumn` = :bidx";
                    $params['bidx'] = array(
                        'value' => BlindIndex::compute($plaintext, $field),
                        'type'  => \PDO::PARAM_STR,
                    );
                }

                $mysql->query("UPDATE `$table` SET $set WHERE `$idColumn` = :id", $params);
                $migrated++;
            }
        }

        return $migrated;
    }

}
