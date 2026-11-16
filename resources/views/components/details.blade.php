{{-- Plain script, not Alpine: an error page holds no Livewire component, so nothing injects Alpine, and a 500 should not depend on it. --}}
<div {{ $attributes->class($class) }} data-error-details>
    <dl class="grid grid-cols-[auto_1fr] gap-x-4 gap-y-1 text-xs" data-error-block>
        @foreach ($rows as $label => $value)
            <div data-field class="contents">
                <dt class="text-zinc-400">{{ $label }}</dt>
                <dd class="break-all tabular-nums text-zinc-500 dark:text-zinc-300">{{ $value }}</dd>
            </div>
        @endforeach
    </dl>

    <div class="mt-3 flex justify-center">
        @if (isset($button) && $button->isNotEmpty())
            {{ $button }}
        @else
            <button type="button" data-error-copy><span data-copy-label>{{ __('request-id::errors.copy') }}</span></button>
        @endif
    </div>

    <script>
        (() => {
            const root = document.currentScript.closest('[data-error-details]');
            const block = root.querySelector('[data-error-block]');
            const label = root.querySelector('[data-copy-label]');
            const labels = { copy: @js(__('request-id::errors.copy')), copied: @js(__('request-id::errors.copied')) };

            const selectBlock = () => {
                const range = document.createRange();
                range.selectNodeContents(block);
                getSelection().removeAllRanges();
                getSelection().addRange(range);
            };

            root.querySelector('[data-error-copy]').addEventListener('click', async () => {
                const lines = [...block.querySelectorAll('[data-field]')]
                    .map((row) => row.querySelector('dt').textContent.trim() + ': ' + row.querySelector('dd').textContent.trim());

                try {
                    await navigator.clipboard.writeText(lines.join('\n'));
                    label.textContent = labels.copied;
                    setTimeout(() => (label.textContent = labels.copy), 2000);
                } catch (error) {
                    selectBlock();
                }
            });
        })();
    </script>
</div>
