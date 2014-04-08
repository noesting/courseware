<?php

namespace Mooc\TestBlock\Model;

use Mooc\TestBlock\Vips\Bridge as VipsBridge;

/**
 * @author Christian Flothmann <christian.flothmann@uos.de>
 */
class Exercise extends \SimpleORMap
{
    /**
     * @var \Exercise The Exercise parsed by Vips
     */
    private $vipsExercise;

    /**
     * @var Solution[] The solutions, one entry per user
     */
    private $solutions;

    /**
     * @var array The solutions parsed by Vips
     */
    private $vipsSolutions;

    /**
     * @var AnswersStrategyInterface The exercise's answers strategy
     */
    private $answersStrategy;

    public function __construct($id = null)
    {
        $this->db_table = 'vips_aufgabe';

        parent::__construct($id);
    }

    /**
     * {@inheritDoc}
     */
    public function setData($data, $reset = false)
    {
        $returnValue = parent::setData($data, $reset);

        if (isset($data['ID']) && $data['ID'] !== null) {
            $type = $this->getType();
            require_once VipsBridge::getVipsPath().'/exercises/'.$type.'.php';
            $this->vipsExercise = new $type($this->Aufgabe, $this->ID);

            $this->answersStrategy = $this->getAnswersStrategy();
        }

        return $returnValue;
    }

    /**
     * Returns the Exercise type.
     *
     * @return string The type
     */
    public function getType()
    {
        return $this->URI;
    }

    /**
     * Returns the Exercise question.
     *
     * @return string The question
     */
    public function getQuestion()
    {
        return $this->vipsExercise->question;
    }

    /**
     * Returns the Exercise answers.
     *
     * @param Test          $test
     * @param \Seminar_User $solver User solving the Exercise
     *
     * @return array The answers
     */
    public function getAnswers(Test $test = null, \Seminar_User $solver = null)
    {
        $answers = array();
        $vipsUrl = VipsBridge::getVipsPlugin()->getPluginURL();
        $solution = null;

        if ($this->hasSolutionFor($test, $solver)) {
            $solution = $this->vipsSolutions[$test->getId()][$solver->cfg->getUserId()];
        }

        foreach ($this->answersStrategy->getAnswers() as $index => $answer) {
            $answers[] = array(
                'text' => $answer,
                'index' => $index,
                'name' => $this->answersStrategy->getName($index),
                'checked' => $this->answersStrategy->isSelected($index, $solution),
                'checked_image' => $vipsUrl.'/images/choice_checked.png',
                'unchecked_image' => $vipsUrl.'/images/choice_unchecked.png',
                'correct_answer' => $this->answersStrategy->isCorrect($index),
            );
        }

        return $answers;
    }

    /**
     * Returns the Solution for a certain test and user.
     *
     * @param Test          $test
     * @param \Seminar_User $user The user
     *
     * @return Solution The Solution or null
     */
    public function getSolutionFor(Test $test, \Seminar_User $user)
    {
        $userId = $user->cfg->getUserId();

        // search for a solution if there is no cached one
        if (!isset($this->solutions[$test->getId()][$userId])) {
            $solution = Solution::findOneBy($test, $this, $user);
            $this->solutions[$test->getId()][$userId] = $solution;
            $this->vipsSolutions[$test->getId()][$userId] = null;

            if ($solution !== null) {
                $this->vipsSolutions[$test->getId()][$userId] = $this->vipsExercise->getTagsFromXML(
                    $solution->solution, 'answer'
                );
            }
        }

        return $this->solutions[$userId];
    }

    /**
     * Checks if there is a Solution for a certain test and user.
     *
     * @param Test          $test
     * @param \Seminar_User $user The user
     *
     * @return boolean True, if there is a Solution for the given user, false
     *                 otherwise
     */
    public function hasSolutionFor(Test $test, \Seminar_User $user)
    {
        // ensure that we check for an existing solution
        $this->getSolutionFor($test, $user);

        return isset($this->solutions[$test->getId()][$user->cfg->getUserId()]);
    }

    /**
     * @return bool True, if the Exercise is a single choice Exercise, false
     *              otherwise
     */
    public function isSingleChoice()
    {
        return $this->getType() == 'sc_exercise';
    }

    /**
     * @return bool True, if the Exercise is a multiple choice Exercise, false
     *              otherwise
     */
    public function isMultipleChoice()
    {
        return $this->getType() == 'mc_exercise';
    }

    /**
     * {@inheritDoc}
     */
    public static function findThru($testId, $options)
    {
        $class = get_called_class();
        $record = new $class();
        $db = \DBManager::get();
        $stmt = $db->prepare(sprintf(
            'SELECT
              t.*
            FROM
              %s AS te
            INNER JOIN
              %s AS t
            ON
              te.%s = t.%s
            WHERE
              te.%s = :test_id
            ORDER BY
              te.position',
            $options['thru_table'],
            $record->db_table,
            $options['thru_assoc_key'],
            $options['assoc_foreign_key'],
            $options['thru_key']
        ));
        $stmt->bindValue(':test_id', $testId);
        $stmt->execute();

        $exercises = array();

        foreach ($stmt->fetchAll(\PDO::FETCH_ASSOC) as $row) {
            $exercise = new $class();
            $exercise->setData($row, true);
            $exercise->setNew(false);

            $exercises[] = $exercise;
        }

        return $exercises;
    }

    /**
     * @return AnswersStrategyInterface
     */
    public function getAnswersStrategy()
    {
        if ($this->answersStrategy === null) {
            $className = null;

            switch ($this->getType()) {
                case 'mc_exercise':
                    $className = 'MultipleChoiceAnswersStrategy';
                    break;
                case 'sc_exercise':
                    $className = 'SingleChoiceAnswersStrategy';
                    break;
                case 'yn_exercise':
                    $className = 'YesNoChoiceAnswersStrategy';
                    break;
            }

            if ($className === null) {
                return null;
            }

            $fullyQualifiedClassName = '\Mooc\TestBlock\Model\\'.$className;

            $this->answersStrategy = new $fullyQualifiedClassName($this->vipsExercise);
        }

        return $this->answersStrategy;
    }
}
