<?php

namespace Core\Model\Encryptor;

/**
 * Class BlindIndex
 * deterministic keyed hash of a two-way encrypted value, stored alongside the
 * ciphertext (e.g. `email_bidx` next to `email`) so exact-match search is
 * possible without decrypting every row. Keyed per field name (not per row),
 * so equal plaintexts always produce equal indexes.
 */
class BlindIndex
{

    /**
     * @param string $plaintext value as it will be looked up (e.g. the submitted email)
     * @param string $fieldName name of the encrypted field this index belongs to
     *
     * @return string hex-encoded HMAC-SHA256
     */
    public static function compute(string $plaintext, string $fieldName): string
    {
        $key = Secret::derive('freimguork:bidx:' . $fieldName);
        return hash_hmac('sha256', self::normalize($plaintext), $key);
    }

    /**
     * default normalization: case/whitespace-insensitive matching (correct for email).
     * pass an already-normalized value in and skip this if a field needs different rules.
     */
    public static function normalize(string $value): string
    {
        return mb_strtolower(trim($value));
    }

}
