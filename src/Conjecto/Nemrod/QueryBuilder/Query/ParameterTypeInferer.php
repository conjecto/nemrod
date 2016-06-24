<?php
/*
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
 * A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT
 * OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
 * LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * This software consists of voluntary contributions made by many individuals
 * and is licensed under the MIT license. For more information, see
 * <http://www.doctrine-project.org>.
 */

namespace Conjecto\Nemrod\QueryBuilder\Query;

use Conjecto\Nemrod\QueryBuilder\SPARQLParserUtils;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Type;
use EasyRdf\Literal;
use EasyRdf\Literal\Boolean;
use EasyRdf\Literal\DateTime;
use EasyRdf\Literal\Integer;

/**
 * Provides an enclosed support for parameter inferring.
 */
class ParameterTypeInferer
{
    /**
     * Infers type of a given value, returning a compatible constant:
     * - Type (\Doctrine\DBAL\Types\Type::*)
     * - Connection (\Doctrine\DBAL\Connection::PARAM_*)
     *
     * @param mixed $value Parameter value.
     *
     * @return mixed Parameter type constant.
     */
    public static function inferType($value)
    {
        if (is_integer($value)) {
            return Integer::class;
        }

        if (is_bool($value)) {
            return Boolean::class;
        }

        if ($value instanceof \DateTime) {
            return DateTime::class;
        }

        if (is_array($value)) {
            return is_integer(current($value))
                ? SPARQLParserUtils::PARAM_INT_ARRAY
                : SPARQLParserUtils::PARAM_STR_ARRAY;
        }



        return Literal::class;
    }
}
