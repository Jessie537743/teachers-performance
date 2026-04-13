<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SentimentLexicon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class SentimentLexiconController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('manage-settings');

        $query = SentimentLexicon::query();

        if ($search = $request->input('search')) {
            $query->where('word', 'like', "%{$search}%");
        }

        if ($polarity = $request->input('polarity')) {
            $query->where('polarity', $polarity);
        }

        if ($language = $request->input('language')) {
            $query->where('language', $language);
        }

        $entries = $query->orderBy('word')->paginate(50)->withQueryString();

        $stats = [
            'total'    => SentimentLexicon::count(),
            'positive' => SentimentLexicon::where('polarity', 'positive')->count(),
            'negative' => SentimentLexicon::where('polarity', 'negative')->count(),
            'neutral'  => SentimentLexicon::where('polarity', 'neutral')->count(),
        ];

        $languages = SentimentLexicon::whereNotNull('language')
            ->distinct()
            ->pluck('language')
            ->sort()
            ->values();

        return view('admin.sentiment-lexicon', compact('entries', 'stats', 'languages'));
    }

    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('manage-settings');

        $data = $request->validate([
            'word'      => ['required', 'string', 'max:191', 'unique:sentiment_lexicon,word'],
            'polarity'  => ['required', 'in:positive,negative,neutral'],
            'language'  => ['nullable', 'string', 'max:12'],
            'is_active' => ['nullable'],
        ]);

        SentimentLexicon::create([
            'word'      => mb_strtolower(trim($data['word'])),
            'polarity'  => $data['polarity'],
            'language'  => $data['language'] ?: null,
            'is_active' => isset($data['is_active']),
        ]);

        return back()->with('success', "Word \"{$data['word']}\" added successfully.");
    }

    public function update(Request $request, SentimentLexicon $sentimentLexicon): RedirectResponse
    {
        Gate::authorize('manage-settings');

        $data = $request->validate([
            'word'      => ['required', 'string', 'max:191', "unique:sentiment_lexicon,word,{$sentimentLexicon->id}"],
            'polarity'  => ['required', 'in:positive,negative,neutral'],
            'language'  => ['nullable', 'string', 'max:12'],
            'is_active' => ['nullable'],
        ]);

        $sentimentLexicon->update([
            'word'      => mb_strtolower(trim($data['word'])),
            'polarity'  => $data['polarity'],
            'language'  => $data['language'] ?: null,
            'is_active' => isset($data['is_active']),
        ]);

        return back()->with('success', "Word \"{$data['word']}\" updated successfully.");
    }

    public function destroy(SentimentLexicon $sentimentLexicon): RedirectResponse
    {
        Gate::authorize('manage-settings');

        $word = $sentimentLexicon->word;
        $sentimentLexicon->delete();

        return back()->with('success', "Word \"{$word}\" deleted.");
    }
}
