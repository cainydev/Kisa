@props([
    'steps' => [],
    'currentStep' => null,
    'completedSteps' => [],
    'allowClickNavigation' => true,
    'id' => null,
])

@php
    $componentId = $id ?? 'wizard-' . uniqid();
    $stepKeys = collect($steps)->pluck('key')->toArray();
@endphp

<div
    x-data="{
        steps: @js($stepKeys),
        currentStep: @js($currentStep ?? $stepKeys[0] ?? null),
        completedSteps: @js($completedSteps),
        allowClickNavigation: @js($allowClickNavigation),

        init() {
            if (!this.currentStep && this.steps.length > 0) {
                this.currentStep = this.steps[0];
            }

            this.$dispatch('step-changed', {
                step: this.currentStep,
                index: this.getStepIndex(this.currentStep)
            });
        },

        goToStep(stepKey) {
            if (!this.isStepAccessible(stepKey)) return;

            this.currentStep = stepKey;
            this.scroll();

            this.$dispatch('step-changed', {
                step: this.currentStep,
                index: this.getStepIndex(this.currentStep)
            });
        },

        goToNextStep() {
            let nextStepIndex = this.getStepIndex(this.currentStep) + 1;
            if (nextStepIndex >= this.steps.length) return;
            this.goToStep(this.steps[nextStepIndex]);
        },

        goToPreviousStep() {
            let previousStepIndex = this.getStepIndex(this.currentStep) - 1;
            if (previousStepIndex < 0) return;
            this.goToStep(this.steps[previousStepIndex]);
        },

        markStepCompleted(stepKey) {
            if (!this.completedSteps.includes(stepKey)) {
                this.completedSteps.push(stepKey);
            }
            this.$dispatch('step-completed', { step: stepKey, completedSteps: this.completedSteps });
        },

        markStepIncomplete(stepKey) {
            const index = this.completedSteps.indexOf(stepKey);
            if (index > -1) {
                this.completedSteps.splice(index, 1);
            }
            this.$dispatch('step-incomplete', { step: stepKey, completedSteps: this.completedSteps });
        },

        isStepCompleted(stepKey) {
            return this.completedSteps.includes(stepKey);
        },

        isStepAccessible(stepKey) {
            return true;
        },

        getStepIndex(stepKey) {
            let index = this.steps.findIndex(step => step === stepKey);
            return index === -1 ? 0 : index;
        },

        isFirstStep() {
            return this.getStepIndex(this.currentStep) <= 0;
        },

        isLastStep() {
            return this.getStepIndex(this.currentStep) + 1 >= this.steps.length;
        },

        scroll() {
            this.$nextTick(() => {
                if (this.$refs.header) {
                    const stepButton = this.$refs.header.children[this.getStepIndex(this.currentStep)];
                    if (stepButton) {
                        stepButton.scrollIntoView({
                            behavior: 'smooth',
                            block: 'start',
                            inline: 'start'
                        });
                    }
                }
            });
        },

        handleScroll(event) {
            event.preventDefault();
            if (this.$refs.header) {
                this.$refs.header.scrollLeft += event.deltaY * 2;
            }
        }
    }"
    {{ $attributes->merge(['id' => $componentId, 'class' => 'fi-sc-wizard']) }}>
    <ol
        role="list"
        x-ref="header"
        class="fi-sc-wizard-header"
    >
        @foreach ($steps as $index => $step)
            <li
                class="fi-sc-wizard-header-step"
                x-bind:class="{
                    'fi-active': currentStep === @js($step['key']),
                    'fi-completed': isStepCompleted(@js($step['key'])),
                }"
            >
                <button
                    type="button"
                    x-bind:aria-current="currentStep === @js($step['key']) ? 'step' : null"
                    x-on:click="allowClickNavigation && goToStep(@js($step['key']))"
                    x-bind:disabled="!allowClickNavigation || !isStepAccessible(@js($step['key']))"
                    role="step"
                    class="fi-sc-wizard-header-step-btn"
                >
                    <div class="fi-sc-wizard-header-step-icon-ctn">
                        <svg
                            x-cloak
                            x-show="isStepCompleted(@js($step['key']))"
                            class="fi-icon-svg fi-size-lg"
                            fill="currentColor"
                            viewBox="0 0 20 20"
                        >
                            <path fill-rule="evenodd"
                                  d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                                  clip-rule="evenodd"></path>
                        </svg>

                        <span
                            x-show="!isStepCompleted(@js($step['key']))"
                            class="fi-sc-wizard-header-step-number"
                        >
                            {{ str_pad($index + 1, 2, '0', STR_PAD_LEFT) }}
                        </span>
                    </div>

                    <div class="fi-sc-wizard-header-step-text">
                        <span class="fi-sc-wizard-header-step-label">
                            {{ $step['label'] }}
                        </span>

                        @if (isset($step['description']) && $step['description'])
                            <span class="fi-sc-wizard-header-step-description">
                                {{ $step['description'] }}
                            </span>
                        @endif
                    </div>
                </button>

                @if (!$loop->last)
                    <svg
                        fill="none"
                        preserveAspectRatio="none"
                        viewBox="0 0 22 80"
                        aria-hidden="true"
                        class="fi-sc-wizard-header-step-separator"
                    >
                        <path
                            d="M0 -2L20 40L0 82"
                            stroke-linejoin="round"
                            stroke="currentcolor"
                            vector-effect="non-scaling-stroke"
                        ></path>
                    </svg>
                @endif
            </li>
        @endforeach
    </ol>
</div>
