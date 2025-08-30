{{-- Care about people's approval and you will be their prisoner. --}}

<div
    x-data="{
        scrollTo(elem) {
            if (elem) {
                elem.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start',
                    inline: 'start'
                });
            }
        },
        scrollToCurrentStep() {
            this.$nextTick(() => {
                const currentStepElement = this.$refs.header.children[@js($this->getStepIndex($this->currentStep))];
                this.scrollTo(currentStepElement);
            });
        }
    }"
    x-init="scrollToCurrentStep()"
    class="fi-sc-wizard"
>
    <ol
        role="list"
        x-ref="header"
        class="fi-sc-wizard-header rounded-none ring-gray-200 dark:ring-white/5"
    >
        @foreach ($steps as $index => $step)
            <li
                @class([
                    'fi-sc-wizard-header-step',
                    'fi-active' => intval($currentStep) === intval($step['key']),
                    'fi-completed' => $this->isStepCompleted($step['key']),
                ])
            >
                <button
                    type="button"
                    wire:click="goToStep('{{ $step['key'] }}')"
                    x-on:click="setTimeout(() => scrollTo($el), 30)"
                    class="fi-sc-wizard-header-step-btn"
                >
                    <div class="fi-sc-wizard-header-step-icon-ctn">
                        @if ($this->isStepCompleted($step['key']))
                            <x-filament::icon
                                :icon="\Filament\Support\Icons\Heroicon::Check"
                                class="fi-color-success"
                            />
                        @else
                            <span class="fi-sc-wizard-header-step-number">
                                {{ str_pad($index + 1, 2, '0', STR_PAD_LEFT) }}
                            </span>
                        @endif
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
