@php
    use App\Livewire\CustomWizard\Step;
    use Filament\Schemas\View\SchemaIconAlias;
    use Filament\Support\Enums\IconSize;
    use Filament\Support\Facades\FilamentAsset;
    use Filament\Support\Icons\Heroicon;
    use Illuminate\View\ComponentAttributeBag;
    use function Filament\Support\generate_icon_html;

    $isContained = $isContained();
    $key = $getKey();
    $previousAction = $getAction('previous');
    $nextAction = $getAction('next');
    $steps = $getChildSchema()->getComponents();
    $footerContent = $getFooterContent();

    $isStepCompleted = function ($step, $index) use ($steps) {
        if (method_exists($step, 'isCompleted') && $step->isCompleted()) {
            return true;
        }

        return false;
    };
@endphp

<div
    x-load
    x-load-src="{{ FilamentAsset::getAlpineComponentSrc('wizard', 'filament/schemas') }}"
    x-data="wizardSchemaComponent({
        isSkippable: @js($isSkippable()),
        isStepPersistedInQueryString: @js($isStepPersistedInQueryString()),
        key: @js($key),
        startStep: @js($getStartStep()),
        stepQueryStringKey: @js($getStepQueryStringKey()),
    })"
    x-on:next-wizard-step.window="if ($event.detail.key === @js($key)) goToNextStep()"
    wire:ignore.self
    {{
        $attributes
            ->merge(['id' => $getId()], escape: false)
            ->merge($getExtraAttributes(), escape: false)
            ->merge($getExtraAlpineAttributes(), escape: false)
            ->class([
                'fi-sc-wizard',
                'fi-contained' => $isContained,
            ])
    }}
>
    <input
        type="hidden"
        value="{{
            collect($steps)
                ->filter(static fn (\Filament\Schemas\Components\Wizard\Step $step): bool => $step->isVisible())
                ->map(static fn (\Filament\Schemas\Components\Wizard\Step $step): ?string => $step->getKey())
                ->values()
                ->toJson()
        }}"
        x-ref="stepsData"
    />

    {{-- Header --}}
    <ol
        @if (filled($label = $getLabel()))
            aria-label="{{ $label }}"
        @endif
        role="list"
        x-cloak
        x-ref="header"
        class="fi-sc-wizard-header"
    >
        @foreach ($steps as $step)
            @php
                /** @var Step $step */
                $conditionalCompleted = $isStepCompleted($step, $loop->index);
            @endphp
            <li
                class="fi-sc-wizard-header-step"
                x-bind:class="{
                    'fi-active': getStepIndex(step) === {{ $loop->index }},
                    'fi-completed': @js($conditionalCompleted),
                }"
            >
                <button
                    type="button"
                    x-bind:aria-current="getStepIndex(step) === {{ $loop->index }} ? 'step' : null"
                    x-on:click="step = @js($step->getKey())"
                    x-bind:disabled="! (isStepAccessible(@js($step->getKey())) || @js($conditionalCompleted) || @js($isSkippable())) || @js($previousAction->isDisabled())"
                    role="step"
                    class="fi-sc-wizard-header-step-btn"
                >
                    <div class="fi-sc-wizard-header-step-icon-ctn">
                        @php
                            $completedIcon = $step->getCompletedIcon();
                        @endphp

                        {{
                            generate_icon_html(
                                $completedIcon ?? Heroicon::OutlinedCheck,
                                alias: filled($completedIcon) ? null : SchemaIconAlias::COMPONENTS_WIZARD_COMPLETED_STEP,
                                attributes: new ComponentAttributeBag([
                                    'x-cloak' => 'x-cloak',
                                    'x-show' => $conditionalCompleted ? 'true' : 'false',
                                ]),
                                size: IconSize::Large,
                            )
                        }}

                        @if (filled($icon = $step->getIcon()))
                            {{
                                generate_icon_html(
                                    $icon,
                                    attributes: new ComponentAttributeBag([
                                        'x-cloak' => 'x-cloak',
                                        'x-show' => $conditionalCompleted ? 'false' : 'true',
                                    ]),
                                    size: IconSize::Large,
                                )
                            }}
                        @else
                            <span
                                x-show="{{ $conditionalCompleted ? 'false' : 'true' }}"
                                class="fi-sc-wizard-header-step-number"
                            >
                                {{ str_pad($loop->index + 1, 2, '0', STR_PAD_LEFT) }}
                            </span>
                        @endif
                    </div>

                    <div class="fi-sc-wizard-header-step-text">
                        @if (! $step->isLabelHidden())
                            <span class="fi-sc-wizard-header-step-label">
                                {{ $step->getLabel() }}
                            </span>
                        @endif

                        @if (filled($description = $step->getDescription()))
                            <span class="fi-sc-wizard-header-step-description">
                                {{ $description }}
                            </span>
                        @endif
                    </div>
                </button>

                @if (! $loop->last)
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

    {{-- Steps Content --}}
    @foreach ($steps as $step)
        {{ $step }}
    @endforeach

    {{-- Footer with custom middle slot --}}
    <div x-cloak
         class="fi-sc-wizard-footer grid grid-cols-[auto_1fr_auto] items-center justify-items-center gap-x-3 px-6 pb-6">
        <div class="col-start-1"
             x-cloak
             @if (! $previousAction->isDisabled())
                 x-on:click="goToPreviousStep"
             @endif
             x-bind:class="{ 'invisible': isFirstStep() }"
        >
            {{ $previousAction }}
        </div>

        <div class="fi-sc-wizard-footer-center col-start-2">
            {{ $footerContent }}
        </div>

        <div
            class="col-start-3"
            x-cloak
            @if (! $nextAction->isDisabled())
                x-on:click="requestNextStep()"
            @endif
            x-bind:class="{ 'invisible': isLastStep() }"
            wire:loading.class="fi-disabled"
        >
            {{ $nextAction }}
        </div>
    </div>
</div>
