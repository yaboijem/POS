@props(['labels' => [], 'values' => [], 'height' => 260, 'width' => 800, 'maxLabels' => 8])

@php
    $gradientId = 'sales-area-'.uniqid();
    $padLeft = 58;
    $padRight = 40;
    $padTop = 18;
    $padBottom = 30;
    $innerWidth = $width - $padLeft - $padRight;
    $innerHeight = $height - $padTop - $padBottom;
    $count = max(count($values), 1);
    $rawMax = max(array_merge([0], $values));

    if ($rawMax <= 0) {
        $niceMax = 100;
    } else {
        $power = 10 ** floor(log10($rawMax));
        $fraction = $rawMax / $power;
        $niceFraction = $fraction <= 1 ? 1 : ($fraction <= 2 ? 2 : ($fraction <= 5 ? 5 : 10));
        $niceMax = $niceFraction * $power;
    }

    $stepX = $count > 1 ? $innerWidth / ($count - 1) : 0;
    $points = [];

    foreach ($values as $index => $value) {
        $x = $count > 1 ? $padLeft + ($index * $stepX) : $padLeft + ($innerWidth / 2);
        $y = $padTop + $innerHeight - (($value / $niceMax) * $innerHeight);
        $points[] = ['x' => round($x, 2), 'y' => round($y, 2), 'value' => $value, 'label' => $labels[$index] ?? ''];
    }

    $line = collect($points)->map(fn ($point) => $point['x'].','.$point['y'])->implode(' ');
    $areaStartX = $points[0]['x'] ?? $padLeft;
    $areaEndX = $points[count($points) - 1]['x'] ?? ($padLeft + $innerWidth);
    $baseline = $padTop + $innerHeight;
    $area = count($points) > 0
        ? 'M '.$areaStartX.' '.$baseline.' L '.collect($points)->map(fn ($point) => $point['x'].' '.$point['y'])->implode(' L ').' L '.$areaEndX.' '.$baseline.' Z'
        : '';

    $compactAmount = function (float $amount): string {
        if ($amount >= 1000) {
            return '₱'.rtrim(rtrim(number_format($amount / 1000, 1), '0'), '.').'k';
        }

        return '₱'.number_format($amount, 0);
    };

    $labelEvery = $count > $maxLabels ? (int) ceil(($count - 1) / ($maxLabels - 1)) : 1;
@endphp

<svg viewBox="0 0 {{ $width }} {{ $height }}" class="h-auto w-full" preserveAspectRatio="xMidYMid meet" role="img" aria-label="Sales trend chart">
    @for ($i = 0; $i <= 4; $i++)
        @php($gridY = $padTop + ($innerHeight / 4) * $i)
        <line x1="{{ $padLeft }}" y1="{{ $gridY }}" x2="{{ $width - $padRight }}" y2="{{ $gridY }}" stroke="#E2E8F0" stroke-width="1" />
        <text x="{{ $padLeft - 10 }}" y="{{ $gridY + 4 }}" text-anchor="end" fill="#64748B" font-size="11" font-family="ui-sans-serif, system-ui">{{ $compactAmount($niceMax * (1 - $i / 4)) }}</text>
    @endfor

    @if ($area)
        <defs>
            <linearGradient id="{{ $gradientId }}" x1="0" y1="0" x2="0" y2="1">
                <stop offset="0%" stop-color="#F97316" stop-opacity="0.20" />
                <stop offset="100%" stop-color="#F97316" stop-opacity="0.01" />
            </linearGradient>
        </defs>
        <path d="{{ $area }}" fill="url(#{{ $gradientId }})" />
        <polyline points="{{ $line }}" fill="none" stroke="#F97316" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" />
    @endif

    @foreach ($points as $index => $point)
        @if ($point['value'] > 0)
            <circle cx="{{ $point['x'] }}" cy="{{ $point['y'] }}" r="3.5" fill="#FFFFFF" stroke="#F97316" stroke-width="2">
                <title>{{ $point['label'] }}: {{ peso($point['value']) }}</title>
            </circle>
        @endif
        @if ($index % $labelEvery === 0 || $index === $count - 1)
            <text x="{{ $point['x'] }}" y="{{ $height - 8 }}" text-anchor="middle" fill="#64748B" font-size="10" font-family="ui-sans-serif, system-ui">{{ $point['label'] }}</text>
        @endif
    @endforeach
</svg>
