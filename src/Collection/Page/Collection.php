<?php
/*
 * Copyright (c) Arnaud Ligny <arnaud@ligny.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cecil\Collection\Page;

use Cecil\Collection\Collection as CecilCollection;

/**
 * Class Collection.
 */
class Collection extends CecilCollection
{
    /**
     * Sort items by date.
     *
     * @return self
     */
    public function sortByDate(): self
    {
        return $this->usort(function ($a, $b) {
            if (!isset($a['date'])) {
                return -1;
            }
            if (!isset($b['date'])) {
                return 1;
            }
            if ($a['date'] == $b['date']) {
                return 0;
            }

            return ($a['date'] > $b['date']) ? -1 : 1;
        });
    }
}
