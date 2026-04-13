{{-- Single optional comment field; place after E. General Observation when possible. --}}
<div class="bg-white border border-gray-200 rounded-2xl shadow-sm overflow-hidden mb-4">
    <div class="px-5 py-3.5 border-b border-gray-200 flex justify-between items-center gap-3">
        <div>
            <span class="font-semibold text-gray-900">Evaluator comments</span>
            <p class="text-sm text-gray-500 mt-0.5">Optional feedback or observations from the evaluator.</p>
        </div>
    </div>
    <div class="p-5">
        <label class="block text-sm font-medium text-gray-700 mb-1.5" for="comment">Comments (optional)</label>
        <textarea name="comment" id="comment"
                  class="w-full rounded-xl border border-gray-300 px-4 py-2.5 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition min-h-[120px]"
                  placeholder="Write your comments here..."
                  maxlength="2000">{{ old('comment') }}</textarea>
    </div>
</div>
