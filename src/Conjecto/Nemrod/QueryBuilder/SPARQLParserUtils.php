<?php

namespace Conjecto\Nemrod\QueryBuilder;

/**
 * Utility class that parses sparql statements with regard to types and parameters.
 *
 * @link   www.doctrine-project.org
 * @since  2.0
 * @author Benjamin Eberlei <kontakt@beberlei.de>
 */
class SPARQLParserUtils
{
    const NAMED_TOKEN      = '(?<!#)#[a-zA-Z_][a-zA-Z0-9_]*';

    // Quote characters within string literals can be preceded by a backslash.
    const ESCAPED_SINGLE_QUOTED_TEXT = "'(?:[^'\\\\]|\\\\'?)*'";
    const ESCAPED_DOUBLE_QUOTED_TEXT = '"(?:[^"\\\\]|\\\\"?)*"';
    const ESCAPED_BACKTICK_QUOTED_TEXT = '`(?:[^`\\\\]|\\\\`?)*`';
    const ESCAPED_BRACKET_QUOTED_TEXT = '\[(?:[^\]])*\]';

    /**
     * Gets an array of the placeholders in an sql statements as keys and their positions in the query string.
     *
     * Returns an integer => integer pair (indexed from zero) for a positional statement
     * and a string => int[] pair for a named statement.
     *
     * @param string  $statement
     * @param boolean $isPositional
     *
     * @return array
     */
    static public function getPlaceholderPositions($statement)
    {
        $match = '#';
        if (strpos($statement, $match) === false) {
            return array();
        }

        $token = self::NAMED_TOKEN;
        $paramMap = array();

        foreach (self::getUnquotedStatementFragments($statement) as $fragment) {
            preg_match_all("/$token/", $fragment[0], $matches, PREG_OFFSET_CAPTURE);
            foreach ($matches[0] as $placeholder) {
                $pos = $placeholder[1] + $fragment[1];
                $paramMap[$pos] = substr($placeholder[0], 1, strlen($placeholder[0]));
            }
        }

        return $paramMap;
    }

    /**
     * For a positional query this method can rewrite the sql statement with regard to array parameters.
     *
     * @param string $query  The SQL query to execute.
     * @param array  $params The parameters to bind to the query.
     * @param array  $types  The types the previous parameters are in.
     *
     * @return array
     *
     * @throws SQLParserUtilsException
     */
    static public function expandListParameters($query, $params)
    {
        $arrayPositions = array();

        $paramPos = self::getPlaceholderPositions($query);

        $queryOffset = 0;
        $paramsOrd   = array();

        foreach ($paramPos as $pos => $paramName) {
            $paramLen = strlen($paramName) + 1;
            $value    = static::extractParam($paramName, $params);

            if ( ! isset($arrayPositions[$paramName]) && ! isset($arrayPositions[':' . $paramName])) {
                $pos         += $queryOffset;
                $queryOffset -= ($paramLen - 1);
                $paramsOrd[]  = $value;
                $query        = substr($query, 0, $pos) . '?' . substr($query, ($pos + $paramLen));

                continue;
            }

            $count      = count($value);
            $expandStr  = $count > 0 ? implode(', ', array_fill(0, $count, '?')) : 'NULL';

            foreach ($value as $val) {
                $paramsOrd[] = $val;
            }

            $pos         += $queryOffset;
            $queryOffset += (strlen($expandStr) - $paramLen);
            $query        = substr($query, 0, $pos) . $expandStr . substr($query, ($pos + $paramLen));
        }

        return array($query, $paramsOrd);
    }

    /**
     * Slice the SQL statement around pairs of quotes and
     * return string fragments of SQL outside of quoted literals.
     * Each fragment is captured as a 2-element array:
     *
     * 0 => matched fragment string,
     * 1 => offset of fragment in $statement
     *
     * @param string $statement
     * @return array
     */
    static private function getUnquotedStatementFragments($statement)
    {
        $literal = self::ESCAPED_SINGLE_QUOTED_TEXT . '|' .
                   self::ESCAPED_DOUBLE_QUOTED_TEXT . '|' .
                   self::ESCAPED_BACKTICK_QUOTED_TEXT . '|' .
                   self::ESCAPED_BRACKET_QUOTED_TEXT;
        preg_match_all("/([^'\"`\[]+)(?:$literal)?/s", $statement, $fragments, PREG_OFFSET_CAPTURE);

        return $fragments[1];
    }

    /**
     * @param string    $paramName      The name of the parameter (without a colon in front)
     * @param array     $paramsOrTypes  A hash of parameters or types
     * @param mixed     $defaultValue   An optional default value. If omitted, an exception is thrown
     *
     * @throws SQLParserUtilsException
     * @return mixed
     */
    static private function extractParam($paramName, $paramsOrTypes, $defaultValue = null)
    {
        if (array_key_exists($paramName, $paramsOrTypes)) {
            return $paramsOrTypes[$paramName];
        }

        // Hash keys can be prefixed with a colon for compatibility
        if (array_key_exists(':' . $paramName, $paramsOrTypes)) {
            return $paramsOrTypes[':' . $paramName];
        }

        if (null !== $defaultValue) {
            return $defaultValue;
        }

        throw SQLParserUtilsException::missingParam($paramName);
    }
}
