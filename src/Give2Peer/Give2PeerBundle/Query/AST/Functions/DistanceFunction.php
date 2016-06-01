<?php

namespace Give2Peer\Give2PeerBundle\Query\AST\Functions;

use Doctrine\ORM\Query\AST\Functions\FunctionNode;
use Doctrine\ORM\Query\Lexer;
use Doctrine\ORM\Query\SqlWalker;
use Doctrine\ORM\Query\Parser;
use Doctrine\ORM\Query\AST\PathExpression;

/**
 * DQL function for calculating distances in meters between two points A and B.
 *
 * This only works with postgreSQL.
 * It is lazily added, because we don't always need it.
 *
 * DISTANCE(:latitudeA, :longitudeA, :latitudeB, :longitudeB)
 */
class DistanceFunction extends FunctionNode
{
    private $latitudeA;
    private $longitudeA;
    private $latitudeB;
    private $longitudeB;

    /**
     * Returns SQL representation of this function.
     *
     * @param SqlWalker $sqlWalker
     *
     * @return string
     */
    public function getSql(SqlWalker $sqlWalker)
    {
        return sprintf('earth_distance(ll_to_earth(%s, %s),ll_to_earth(%s, %s))',
            $this->latitudeA->dispatch($sqlWalker),
            $this->longitudeA->dispatch($sqlWalker),
            $this->latitudeB->dispatch($sqlWalker),
            $this->longitudeB->dispatch($sqlWalker)
        );
    }

    /**
     * Parses DQL function.
     *
     * @param Parser $parser
     */
    public function parse(Parser $parser)
    {
        $parser->match(Lexer::T_IDENTIFIER);
        $parser->match(Lexer::T_OPEN_PARENTHESIS);
        $this->latitudeA = $parser->ArithmeticPrimary();
        $parser->match(Lexer::T_COMMA);
        $this->longitudeA = $parser->ArithmeticPrimary();
        $parser->match(Lexer::T_COMMA);
        $this->latitudeB = $parser->ArithmeticPrimary();
        $parser->match(Lexer::T_COMMA);
        $this->longitudeB = $parser->ArithmeticPrimary();
        $parser->match(Lexer::T_CLOSE_PARENTHESIS);
    }
}
