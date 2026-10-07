<?php

namespace App\Filament\Support;

use Filament\Actions\Action;
use Filament\Forms\Components\CodeEditor;
use Filament\Forms\Components\CodeEditor\Enums\Language;
use Filament\Forms\Components\RichEditor;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;

/**
 * The rich text field used everywhere in the admin. Every editor gets the same toolbar, with no
 * alignment or colour tools because the website's design decides how text looks, and an
 * "Edit HTML" button for pasting or writing HTML directly. Whatever is entered, the public
 * site prints it through App\Cms\RichTextSanitizer.
 */
class RichTextEditor
{
    /** @var array<int, array<int, string>> */
    public const array STANDARD_TOOLBAR = [
        ['bold', 'italic', 'underline', 'strike', 'link'],
        ['h2', 'h3', 'h4'],
        ['bulletList', 'orderedList', 'blockquote', 'codeBlock', 'horizontalRule'],
        ['table', 'attachFiles'],
        ['clearFormatting', 'undo', 'redo'],
    ];

    /** @var array<int, array<int, string>> */
    public const array BASIC_TOOLBAR = [
        ['bold', 'italic', 'underline', 'link'],
        ['bulletList', 'orderedList'],
        ['clearFormatting', 'undo', 'redo'],
    ];

    /**
     * @param  array<int, array<int, string>>  $toolbar
     */
    public static function make(string $name, array $toolbar = self::STANDARD_TOOLBAR): RichEditor
    {
        return RichEditor::make($name)
            ->toolbarButtons($toolbar)
            ->afterLabel([static::editHtmlAction()])
            ->columnSpanFull();
    }

    /**
     * Opens the field's current content as HTML and puts the edited HTML back into the editor.
     * The editor keeps what it can represent (headings, paragraphs, lists, links, tables, images).
     */
    public static function editHtmlAction(): Action
    {
        return Action::make('editHtml')
            ->label('Edit HTML')
            ->icon(Heroicon::OutlinedCodeBracket)
            ->link()
            ->modalHeading('Edit HTML')
            ->modalDescription('Paste or write HTML. Headings, paragraphs, lists, links, tables and images are kept. Inline styles, classes, scripts and layout tags are removed, so the text matches the website design.')
            ->modalWidth(Width::FiveExtraLarge)
            ->modalSubmitActionLabel('Apply HTML')
            ->fillForm(fn (RichEditor $component): array => ['html' => static::readableHtml($component->getState())])
            ->schema([
                CodeEditor::make('html')
                    ->hiddenLabel()
                    ->language(Language::Html)
                    ->wrap(),
            ])
            ->action(function (array $data, RichEditor $component): void {
                $component->state(filled($data['html'] ?? null) ? $data['html'] : null);
            });
    }

    /**
     * The editor stores HTML on one line; start each block on its own line so it can be read and edited.
     */
    public static function readableHtml(mixed $html): string
    {
        if (! is_string($html) || blank(strip_tags($html, '<img>'))) {
            return '';
        }

        return trim((string) preg_replace(
            '#(</(?:p|h[1-6]|ul|ol|li|blockquote|pre|table|thead|tbody|tfoot|tr|figure)>|<hr\s*/?>)(?=<)#i',
            "$1\n",
            $html,
        ));
    }
}
