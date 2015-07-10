define(['assets/js/student_view', 'assets/js/url'], function (StudentView, helper) {
    'use strict';

    return StudentView.extend({
        events: {
            'click button[name=reset-exercise]': function (event) {
                var $form = this.$(event.target).closest('form'),
                    view = this,
                    $exercise_index = $form.find("input[name='exercise_index']").val(),
                    $block = this.$el.parent();

                if (confirm('Soll die Antwort zurückgesetzt werden?')) {
                    helper.callHandler(this.model.id, 'exercise_reset', $form.serialize())
                        .then(
                            function () {
                                return view.renderServerSide();
                            },
                            function () {
                                console.log('failed to reset the exercise');
                            }
                        )
                        .then(function () {
                            $block.find('.exercise').hide();
                            $block.find('#exercise' + $exercise_index).show();
                        })
                        .done();
                }

                return false;
            },

            'click button[name=submit-exercise]': function (event) {
                var $form = this.$(event.target).closest('form'),
                    view = this,
                    $exercise_index = $form.find("input[name='exercise_index']").val(),
                    $block = this.$el.parent();

                helper.callHandler(this.model.id, 'exercise_submit', $form.serialize())
                    .then(
                        function () {
                            return view.renderServerSide();
                        },
                        function () {
                            console.log('failed to store the solution');
                        }
                    )
                    .then(function () {
                        view.$('.exercise').hide();
                        view.$('#exercise' + $exercise_index).show();
                    })
                    .done();

                return false;
            },

            'click button[name=exercisenav]': function (event){
                var options = $.parseJSON(this.$(event.target).attr('button-data')),
                    $num = parseInt(options.id);

                if (options.direction == 'next') {
                    $num++;
                } else {
                    $num--;
                }

                if ($num > parseInt(options.numexes, 10)) {
                    $num = 1;
                }

                if ($num < 1) {
                    $num = parseInt(options.numexes, 10);
                }
                var $block = this.$el.parent();

                // FIXME
                $block.find('.exercise').hide();
                $block.find('#exercise'+$num).show();
            }
        },

        initialize: function(options) {
        },

        render: function() {
            return this;
        },

        postRender: function () {
            var view = this;
            var fixAnswersHeight = function (labels, answers) {
                for (var i = 0; i < labels.length && i < answers.length; i++) {
                    var answer = answers.eq(i);
                    answer.css({height: 'auto'});
                    var label = labels.eq(i);
                    label.css({height: 'auto'});
                    var labelHeight = label.height();
                    var answerHeight = answer.height();

                    if (labelHeight > answerHeight) {
                        answer.css({height: labelHeight});
                    } else if (labelHeight < answerHeight) {
                        label.css({height: answerHeight});
                    }
                }
            };

            this.$('ul.exercise_answers').each(function () {
                var $sortableAnswers = $(this),
                    $sortableLabels = $sortableAnswers.parent().find('ul.matching_exercise.labels');

                fixAnswersHeight($sortableAnswers.find('li'), $sortableLabels.find('li'));

                $sortableAnswers.sortable({
                    axis: 'y',
                    containment: $sortableAnswers,
                    tolerance: 'pointer',
                    update: function () {
                        view.moveChoice($sortableAnswers);
                        fixAnswersHeight($sortableAnswers.find('li'), $sortableLabels.find('li'));
                    },
                    sort: function (event, ui) {
                        // this workaround is needed, otherwise, sortable items
                        // would jump when the user scrolled down before sorting
                        ui.helper.css({
                            top : ui.position.top + $(window).scrollTop() + 'px'
                        });
                    }
                });
            });
        },

        moveChoice: function ($sortableAnswers) {
            var items = $sortableAnswers.sortable('toArray');
            var $inputs = jQuery('input', $sortableAnswers);

            for (var i = 0; i < items.length; i++) {
                $inputs.eq(i).val(i);
            }
        }
    });
});
