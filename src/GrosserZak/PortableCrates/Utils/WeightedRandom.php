<?php
declare(strict_types=1);

namespace GrosserZak\PortableCrates\Utils;

use Ds\Deque;
use Generator;
use pocketmine\utils\Random;

/**
 * This's Muqsit's code check it out on link below
 * @link https://gist.github.com/Muqsit/5042779c0e87fd55e55560f83e24af69
 */

class WeightedRandom {

    /** @var float[] */
    private array $probabilities;

    /** @var int[] */
    private array $aliases = [];

    /** @var Random */
    private Random $random;

    /** @var array */
    private array $indexes = [];

    final public function add($value, float $weight) : void {
        $this->probabilities[] = $weight;
        $this->indexes[] = $value;
    }

    final public function count() : int {
        return count($this->probabilities);
    }

    final public function setup(bool $fill_null = false) : void {
        if($fill_null) {
            $this->add(null, 1.0 - (array_sum($this->probabilities) / $this->count()));
        }

        $this->random = new Random();

        $probabilities_c = $this->count();
        $average = 1.0 / $probabilities_c;
        $probabilities = $this->probabilities;

        $small = new Deque();
        $small->allocate($probabilities_c);
        $large = new Deque();
        $large->allocate($probabilities_c);

        for($i = 0; $i < $probabilities_c; ++$i) {
            if($probabilities[$i] >= $average) {
                $large->push($i);
            } else {
                $small->push($i);
            }
        }

        while(!$small->isEmpty() && !$large->isEmpty()) {
            $less = $small->pop();
            $more = $large->pop();

            $this->probabilities[$less] = $probabilities[$less] * $probabilities_c;
            $this->aliases[$less] = $more;

            $probabilities[$more] = ($probabilities[$more] + $probabilities[$less]) - $average;
            if($probabilities[$more] >= 1.0 / $probabilities_c) {
                $large->push($more);
            } else {
                $small->push($more);
            }
        }

        while(!$small->isEmpty()) {
            $this->probabilities[$small->pop()] = 1.0;
        }
        while(!$large->isEmpty()) {
            $this->probabilities[$large->pop()] = 1.0;
        }
    }

    final public function generateIndexes(int $count) : Generator {
        $probabilities_c = count($this->probabilities);
        while(--$count >= 0) {
            $index = $this->random->nextBoundedInt($probabilities_c);
            yield $this->random->nextFloat() <= $this->probabilities[$index] ? $index : $this->aliases[$index];
        }
    }

    public function generate(int $count) : Generator {
        foreach($this->generateIndexes($count) as $index) {
            yield $this->indexes[$index];
        }
    }

}
