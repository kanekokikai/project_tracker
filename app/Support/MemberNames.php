<?php

namespace App\Support;

class MemberNames
{
    /**
     * Keep names that exactly match an employee, plus names already saved on the topic.
     *
     * @param  list<mixed>  $submitted
     * @param  list<string>  $catalog
     * @param  list<string>  $alreadySaved
     * @return list<string>
     */
    public static function pick(array $submitted, array $catalog, array $alreadySaved = []): array
    {
        $allowed = [];

        foreach ([$catalog, $alreadySaved] as $source) {
            foreach ($source as $name) {
                if (! is_string($name)) {
                    continue;
                }

                $name = trim($name);

                if ($name === '') {
                    continue;
                }

                $allowed[$name] = $name;
            }
        }

        $picked = [];

        foreach ($submitted as $name) {
            if (! is_string($name)) {
                continue;
            }

            $name = trim($name);

            if ($name === '' || ! isset($allowed[$name]) || isset($picked[$name])) {
                continue;
            }

            $picked[$name] = $name;
        }

        return array_values($picked);
    }
}
