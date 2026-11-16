<?php

declare(strict_types=1);

namespace Rozkalns\RequestId\View\Components;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Lang;
use Illuminate\View\Component;
use Rozkalns\RequestId\ErrorPageDetails;

final class Details extends Component
{
    /** @var array<string, string> label => value: the ID, the time and the path first, then the viewer's rows */
    public readonly array $rows;

    /**
     * @param  string  $class  classes of the wrapper
     */
    public function __construct(
        public string $class = 'mt-8 w-full max-w-sm border-t border-zinc-200 pt-4 dark:border-zinc-700',
    ) {
        $details = ErrorPageDetails::current();

        $this->rows = $details instanceof ErrorPageDetails ? array_filter([
            Lang::string('request-id::errors.request_id') => $details->requestId,
            Lang::string('request-id::errors.time') => $details->time,
            Lang::string('request-id::errors.path') => $details->path,
            ...$details->rows,
        ], filled(...)) : [];
    }

    #[\Override]
    public function shouldRender(): bool
    {
        return $this->rows !== [];
    }

    #[\Override]
    public function render(): View
    {
        return view('request-id::components.details');
    }
}
