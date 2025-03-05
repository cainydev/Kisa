import preset from './vendor/filament/support/tailwind.config.preset'

export default {
    presets: [preset],
    content: [
        './app/Filament/**/*.php',
        './resources/**/*.{blade.php,js,html}',
        './vendor/filament/**/*.blade.php',
    ],
    safelist: ['items-stretch']
}
