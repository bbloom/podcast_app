<?php

namespace MediaPlatform\Digest\ContentSources\Lists\Controllers;

use App\Http\Controllers\Controller;
use MediaPlatform\Digest\ContentSources\Lists\Models\ListModel;
use MediaPlatform\Digest\ContentSources\OutputDestinations\Models\OutputDestination;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ListWizardController extends Controller
{
    // -------------------------------------------------------------------------
    // Index
    // -------------------------------------------------------------------------

    public function index()
    {
        $lists = ListModel::where('user_id', auth()->id())
            ->orderBy('name')
            ->paginate(config('admin.pagination_index'));
        return view('media_platform.digest.content_sources.lists.index', compact('lists'));
    }

    // -------------------------------------------------------------------------
    // Step 1: Name, description, timezone
    // -------------------------------------------------------------------------

    public function step1(Request $request)
    {
        // Store redirect_to in session if passed as a query parameter.
        // This allows other wizards to send the user here and get them back
        // automatically after the list is created.
        // e.g. /lists/create/step1?redirect_to=youtube.channels.create.step4
        if ($request->query('redirect_to')) {
            $request->session()->put('list_wizard.redirect_to', $request->query('redirect_to'));
        }

        return view('media_platform.digest.content_sources.lists.wizard-step1');
    }

    public function step1Submit(Request $request)
    {
        $request->validate([
            'name'        => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'timezone'    => ['nullable', 'string', 'timezone'],
        ], [
            'name.required'     => 'Please give this list a name.',
            'timezone.timezone' => 'Please select a valid timezone.',
        ]);

        $request->session()->put('list_wizard.name',        $request->input('name'));
        $request->session()->put('list_wizard.description', $request->input('description'));
        $request->session()->put('list_wizard.timezone',    $request->input('timezone'));

        return redirect()->route('lists.create.step2');
    }

    // -------------------------------------------------------------------------
    // Step 2: Schedule (frequency, day, time)
    // -------------------------------------------------------------------------

    public function step2(Request $request)
    {
        if (! $request->session()->has('list_wizard.name')) {
            return redirect()->route('lists.create.step1');
        }

        return view('media_platform.digest.content_sources.lists.wizard-step2');
    }

    public function step2Submit(Request $request)
    {
        $request->validate([
            'schedule_frequency' => ['required', 'in:daily,weekly,monthly'],
            'schedule_day'       => ['nullable', 'integer', 'min:1', 'max:31'],
            'schedule_time'      => ['required', 'date_format:H:i'],
        ], [
            'schedule_frequency.required' => 'Please select how often this list should run.',
            'schedule_frequency.in'       => 'Please select a valid frequency.',
            'schedule_day.min'            => 'Please select a valid day.',
            'schedule_day.max'            => 'Please select a valid day.',
            'schedule_time.required'      => 'Please select a time of day.',
            'schedule_time.date_format'   => 'Please enter a valid time in HH:MM format.',
        ]);

        $frequency = $request->input('schedule_frequency');
        $day       = $frequency === 'daily' ? null : $request->input('schedule_day');

        if ($frequency === 'weekly' && (! $day || $day < 1 || $day > 7)) {
            return back()->withInput()->withErrors(['schedule_day' => 'Please select a day of the week.']);
        }

        if ($frequency === 'monthly' && (! $day || $day < 1 || $day > 31)) {
            return back()->withInput()->withErrors(['schedule_day' => 'Please select a day of the month.']);
        }

        $request->session()->put('list_wizard.schedule_frequency', $frequency);
        $request->session()->put('list_wizard.schedule_day',       $day);
        $request->session()->put('list_wizard.schedule_time',      $request->input('schedule_time'));

        return redirect()->route('lists.create.step3');
    }

    // -------------------------------------------------------------------------
    // Step 3: Output type (webpage vs email)
    // -------------------------------------------------------------------------

    public function step3(Request $request)
    {
        if (! $request->session()->has('list_wizard.schedule_frequency')) {
            return redirect()->route('lists.create.step1');
        }

        return view('media_platform.digest.content_sources.lists.wizard-step3');
    }

    public function step3Submit(Request $request)
    {
        $request->validate([
            'output_type' => ['required', 'in:webpage,email,wordpress'],
        ], [
            'output_type.required' => 'Please select how you want this list delivered.',
            'output_type.in'       => 'Please select a valid output type.',
        ]);

        $request->session()->put('list_wizard.output_type', $request->input('output_type'));

        if ($request->input('output_type') === 'email') {
            return redirect()->route('lists.create.step6');
        }

        // webpage and wordpress redir to step 4
        return redirect()->route('lists.create.step4');
    }

    // -------------------------------------------------------------------------
    // Step 4: Output destination (webpage and wordpress)
    // -------------------------------------------------------------------------

    public function step4(Request $request)
    {
        if (! in_array($request->session()->get('list_wizard.output_type'), ['webpage', 'wordpress'])) {
            return redirect()->route('lists.create.step1');
        }

        $destinations = OutputDestination::where('user_id', auth()->id())
            ->where('enabled', true)
            ->orderBy('name')
            ->get();

        return view('media_platform.digest.content_sources.lists.wizard-step4', compact('destinations'));
    }

    public function step4Submit(Request $request)
    {
        $request->validate([
            'output_destination_id' => ['required', 'integer'],
        ], [
            'output_destination_id.required' => 'Please select an output destination.',
        ]);

        $destination = OutputDestination::where('id', $request->input('output_destination_id'))
            ->where('user_id', auth()->id())
            ->first();

        if (! $destination) {
            return back()->withErrors(['output_destination_id' => 'The selected destination is not valid.']);
        }

        $request->session()->put('list_wizard.output_destination_id', $destination->id);

        return redirect()->route('lists.create.step5');
    }

    // -------------------------------------------------------------------------
    // Step 5: Email notification preference (webpage only)
    // -------------------------------------------------------------------------

    public function step5(Request $request)
    {
        if (! in_array($request->session()->get('list_wizard.output_type'), ['webpage', 'wordpress'])) {
            return redirect()->route('lists.create.step1');
        }

        if (! $request->session()->has('list_wizard.output_destination_id')) {
            return redirect()->route('lists.create.step4');
        }

        return view('media_platform.digest.content_sources.lists.wizard-step5');
    }

    public function step5Submit(Request $request)
    {
        $request->validate([
            'notify_by_email' => ['required', 'in:1,0'],
        ], [
            'notify_by_email.required' => 'Please select your notification preference.',
        ]);

        $request->session()->put('list_wizard.notify_by_email', (bool) $request->input('notify_by_email'));

        return redirect()->route('lists.create.step6');
    }

    // -------------------------------------------------------------------------
    // Step 6: Confirm and save
    // -------------------------------------------------------------------------

    public function step6(Request $request)
    {
        if (! $request->session()->has('list_wizard.output_type')) {
            return redirect()->route('lists.create.step1');
        }

        $data        = $request->session()->get('list_wizard');
        $destination = null;

        if (in_array($data['output_type'] ?? null, ['webpage', 'wordpress']) && ! empty($data['output_destination_id'])) {
            $destination = OutputDestination::find($data['output_destination_id']);
        }

        return view('media_platform.digest.content_sources.lists.wizard-step6', compact('data', 'destination'));
    }

    public function step6Submit(Request $request)
    {
        if (! $request->session()->has('list_wizard.output_type')) {
            return redirect()->route('lists.create.step1');
        }

        $data                = $request->session()->get('list_wizard');
        $outputType          = $data['output_type'];
        $requiresDestination = in_array($outputType, ['webpage', 'wordpress']);

        ListModel::create([
            'user_id'               => auth()->id(),
            'name'                  => $data['name'],
            'description'           => $data['description'] ?? null,
            'timezone'              => $data['timezone'] ?? null,
            'enabled'               => true,
            'schedule_frequency'    => $data['schedule_frequency'],
            'schedule_day'          => $data['schedule_day'] ?? null,
            'schedule_time'         => $data['schedule_time'],
            'output_type'           => $outputType,
            'output_destination_id' => $requiresDestination ? ($data['output_destination_id'] ?? null) : null,
            'notify_by_email'       => $outputType === 'webpage' ? ($data['notify_by_email'] ?? false) : false,
        ]);

        $redirectTo = $data['redirect_to'] ?? null;

        $request->session()->forget('list_wizard');

        if ($redirectTo && \Illuminate\Support\Facades\Route::has($redirectTo)) {
            return redirect()->route($redirectTo)
                ->with('success', 'List created. Now assign your channel to it.');
        }

        return redirect()->route('lists.create.step7');
    }
    
    // -------------------------------------------------------------------------
    // Step 7: Done
    // -------------------------------------------------------------------------

    public function step7()
    {
        return view('media_platform.digest.content_sources.lists.wizard-step7');
    }

    // -------------------------------------------------------------------------
    // Edit
    // -------------------------------------------------------------------------

    public function edit(ListModel $list)
    {
        $this->authorizeOwnership($list);

        $destinations = OutputDestination::where('user_id', auth()->id())
            ->where('enabled', true)
            ->orderBy('name')
            ->get();

        return view('media_platform.digest.content_sources.lists.edit', compact('list', 'destinations'));
    }

    // -------------------------------------------------------------------------
    // Show
    // -------------------------------------------------------------------------

    /**
     * Display a single list with its sources and tracking status.
     */
    public function show(ListModel $list)
    {
        $this->authorizeOwnership($list);

        $sources = $list->sources()
            ->with('sourceable')
            ->paginate(config('admin.pagination_show'));

        $tracking = \MediaPlatform\Digest\Processing\Models\ListSourceTracking::whereIn(
            'list_source_id',
            $sources->pluck('id')
        )->get()->keyBy('list_source_id');

        return view('media_platform.digest.content_sources.lists.show', compact('list', 'sources', 'tracking'));
    }

    // -------------------------------------------------------------------------
    // Update
    // -------------------------------------------------------------------------

    public function update(Request $request, ListModel $list)
    {
        $this->authorizeOwnership($list);

        $request->validate([
            'name'                  => ['required', 'string', 'max:255'],
            'description'           => ['nullable', 'string', 'max:1000'],
            'timezone'              => ['nullable', 'string', 'timezone'],
            'enabled'               => ['nullable', 'boolean'],
            'schedule_frequency'    => ['required', 'in:daily,weekly,monthly'],
            'schedule_day'          => ['nullable', 'integer', 'min:1', 'max:31'],
            'schedule_time'         => ['required', 'date_format:H:i'],
            'output_type'           => ['required', 'in:webpage,email,wordpress'],
            'output_destination_id' => ['nullable', 'integer'],
            'notify_by_email'       => ['nullable', 'boolean'],
        ]);

        $outputType          = $request->input('output_type');
        $requiresDestination = in_array($outputType, ['webpage', 'wordpress']);
        $destinationId       = null;

        if ($requiresDestination && $request->filled('output_destination_id')) {
            $destination = OutputDestination::where('id', $request->input('output_destination_id'))
                ->where('user_id', auth()->id())
                ->first();

            abort_if(! $destination, 403);
            $destinationId = $destination->id;
        }

        $list->update([
            'name'                  => $request->input('name'),
            'description'           => $request->input('description'),
            'timezone'              => $request->input('timezone'),
            'enabled'               => $request->boolean('enabled'),
            'schedule_frequency'    => $request->input('schedule_frequency'),
            'schedule_day'          => $request->input('schedule_day'),
            'schedule_time'         => $request->input('schedule_time'),
            'output_type'           => $outputType,
            'output_destination_id' => $requiresDestination ? $destinationId : null,
            'notify_by_email'       => $outputType === 'webpage' ? $request->boolean('notify_by_email') : false,
        ]);

        return redirect()->route('lists.index')
            ->with('success', 'List updated successfully.');
    }

    // -------------------------------------------------------------------------
    // Delete
    // -------------------------------------------------------------------------

    public function confirmDelete(ListModel $list)
    {
        $this->authorizeOwnership($list);

        return view('media_platform.digest.content_sources.lists.delete-confirm', compact('list'));
    }

    public function destroy(ListModel $list)
    {
        $this->authorizeOwnership($list);

        $list->delete();

        return redirect()->route('lists.index')
            ->with('success', 'List deleted.');
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function authorizeOwnership(ListModel $list): void
    {
        abort_if($list->user_id !== auth()->id(), 403);
    }
}