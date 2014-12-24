<?php

/*
 * This file is part of the Certificationy application.
 *
 * (c) Vincent Composieux <vincent.composieux@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Certificationy\Certification;

use Symfony\Component\Finder\Finder;
use Symfony\Component\Yaml\Yaml;

/**
 * Class Loader
 *
 * @author Vincent Composieux <vincent.composieux@gmail.com>
 */
class Loader
{
    /**
     * @var integer
     */
    static public $count;

    /**
     * Returns a new set of randomized questions
     *
     * @param integer $number
     * @param array   $categories
     *
     * @return Set
     */
    static public function init($number, array $categories)
    {
        $data = self::prepareFromYaml($categories);

        if (!$data) {
            return new Set(array());
        }

        $dataMax = count($data) - 1;

        $questions = array();

        for ($i = 0; $i < $number; $i++) {
            do {
                $random = rand(0, $dataMax);
            } while (isset($questions[$random]) && count($questions) < $dataMax);

            $item = $data[$random];

            $answers = array();

            foreach ($item['answers'] as $dataAnswer) {
                $answers[] = new Answer($dataAnswer['value'], $dataAnswer['correct']);
            }

            if (!isset($item['shuffle']) || $item['shuffle'] == true) {
                shuffle($answers);
            }

            $questions[$random] = new Question($item['question'], $item['category'], $answers);
        }

        return new Set($questions);
    }

    /**
     * Counts total of available questions
     *
     * @return integer
     */
    static public function count()
    {
        return self::$count ?: count(self::prepareFromYaml());
    }

    /**
     * Prepares data from Yaml files and returns an array of questions
     *
     * @param array $categories : List of categories which should be included, empty array = all
     *
     * @return array
     */
    static protected function prepareFromYaml(array $categories = array())
    {
        $files = Finder::create()->files()->in(__DIR__ . '/../data/')->name('*.yml');

        $data = array();

        foreach ($files as $file) {
            $fileData = Yaml::parse($file->getContents());

            $category = $fileData['category'];
            if (count($categories) == 0 || in_array($category, $categories)) {
                array_walk($fileData['questions'], function (&$item, $key) use ($category) {
                    $item['category'] = $category;
                });

                $data = array_merge($data, $fileData['questions']);
            }
        }

        self::$count = count($data);

        return $data;
    }
    
    /**
     * Get list of all categories
     * 
     * @return array
     */
    static public function getCategories()
    {
        $categories = array();
        $files      = self::prepareFromYaml();

        foreach($files as $file) {
            $categories[] = $file['category'];
        }
        
        return array_unique($categories);
    }
}
