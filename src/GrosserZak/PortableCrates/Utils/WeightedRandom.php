<?php
declare(strict_types=1);

namespace GrosserZak\PortableCrates\Utils;

use Generator;
use pocketmine\utils\Random;

/**
 * Originally made by Muqsit
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

        $small = [];
        $large = [];

        for($i = 0; $i < $probabilities_c; ++$i) {
            if($probabilities[$i] >= $average) {
                $large[] = $i;
            } else {
                $small[] = $i;
            }
        }

        while(count($small) > 0 && count($large) > 0) {
            $less = array_pop($small);
            $more = array_pop($large);

            $this->probabilities[$less] = $probabilities[$less] * $probabilities_c;
            $this->aliases[$less] = $more;

            $probabilities[$more] = ($probabilities[$more] + $probabilities[$less]) - $average;
            if($probabilities[$more] >= 1.0 / $probabilities_c) {
                $large[] = $more;
            } else {
                $small[] = $more;
            }
        }

        while(count($small) > 0) {
            $this->probabilities[array_pop($small)] = 1.0;
        }
        while(count($large) > 0) {
            $this->probabilities[array_pop($large)] = 1.0;
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
