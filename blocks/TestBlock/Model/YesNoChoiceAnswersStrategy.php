<?php

namespace Mooc\TestBlock\Model;

/**
 * Answers strategy for single choice exercises with only two answers.
 *
 * @author Christian Flothmann <christian.flothmann@uos.de>
 */
class YesNoChoiceAnswersStrategy extends SingleChoiceAnswersStrategy
{
    /**
     * {@inheritDoc}
     */
    public function getAnswers()
    {
        return $this->vipsExercise->answerArray;
    }

    /**
     * {@inheritDoc}
     */
    public function getTemplate()
    {
        return 'single_choice_answers';
    }
}
