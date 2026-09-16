<?php

namespace App\Support;

use Illuminate\Support\HtmlString;

class MarkdownRenderer
{
    public function render(string $markdown): HtmlString
    {
        $lines = preg_split('/\R/', trim($markdown)) ?: [];
        $html = [];
        $inList = false;

        foreach ($lines as $line) {
            $trimmed = trim($line);
            if ($trimmed === '') {
                if ($inList) {
                    $html[] = '</ul>';
                    $inList = false;
                }

                continue;
            }
            if (preg_match('/^(#{1,3})\s+(.+)$/', $trimmed, $matches)) {
                $level = strlen($matches[1]);
                $html[] = sprintf('<h%d class="mt-4 font-semibold">%s</h%d>', $level, $this->inline($matches[2]), $level);

                continue;
            }
            if (preg_match('/^[-*]\s+(.+)$/', $trimmed, $matches)) {
                if (! $inList) {
                    $html[] = '<ul class="my-2 list-disc space-y-1 pl-5">';
                    $inList = true;
                }
                $html[] = '<li>'.$this->inline($matches[1]).'</li>';

                continue;
            }
            if ($inList) {
                $html[] = '</ul>';
                $inList = false;
            }
            if (str_starts_with($trimmed, '> ')) {
                $html[] = '<blockquote class="my-3 border-l-4 border-indigo-300 pl-4 italic text-slate-600">'.$this->inline(substr($trimmed, 2)).'</blockquote>';

                continue;
            }
            if ($trimmed === '---') {
                $html[] = '<hr class="my-4 border-slate-200">';

                continue;
            }
            $html[] = '<p class="my-2 leading-7">'.$this->inline($trimmed).'</p>';
        }
        if ($inList) {
            $html[] = '</ul>';
        }

        return new HtmlString(implode("\n", $html));
    }

    private function inline(string $value): string
    {
        $value = e($value);
        $value = preg_replace('/`([^`]+)`/', '<code class="rounded bg-slate-100 px-1 py-0.5 text-sm">$1</code>', $value) ?? $value;
        $value = preg_replace('/\*\*(.+?)\*\*/', '<strong>$1</strong>', $value) ?? $value;

        return preg_replace('/\*([^*]+)\*/', '<em>$1</em>', $value) ?? $value;
    }
}
