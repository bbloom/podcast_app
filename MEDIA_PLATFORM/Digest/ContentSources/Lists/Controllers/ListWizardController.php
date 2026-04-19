<?php

namespace MediaPlatform\Digest\ContentSources\Lists\Controllers;

use App\Http\Controllers\Controller;
use MediaPlatform\Digest\ContentSources\Lists\Models\ListModel;
use MediaPlatform\Digest\ContentSources\OutputDestinations\Models\OutputDestination;
use MediaPlatform\Digest\Enums\OutputType;
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
            'schedule_frequency.required' => 'Please select a schedule frequency.',
            'schedule_time.required'      => 'Please select a time of day.',
        ]);

        $request->session()->put('list_wizard.schedule_frequency', $request->input('schedule_frequency'));
        $request->session()->put('list_wizard.schedule_day',       $request->input('schedule_day'));
        $request->session()->put('list_wizard.schedule_time',      $request->input('schedule_time'));

        return redirect()->route('lists.create.step3');
    }

    // -------------------------------------------------------------------------
    // Step 3: Output type (webpage, email, static_site)
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
            'output_type' => ['required', 'in:webpage,email,static_site'],
        ], [
            'output_type.required' => 'Please select how you want this list delivered.',
            'output_type.in'       => 'Please select a valid output type.',
        ]);

        $request->session()->put('list_wizard.output_type', $request->input('output_type'));

        if ($request->input('output_type') === 'email') {
            return redirect()->route('lists.create.step6');
        }

        if ($request->input('output_type') === 'static_site') {
            return redirect()->route('lists.create.step4_static_site');
        }

        return redirect()->route('lists.create.step4');
    }

    // -------------------------------------------------------------------------
    // Step 4: Output destination (webpage only)
    // -------------------------------------------------------------------------

    public function step4(Request $request)
    {
        if ($request->session()->get('list_wizard.output_type') !== 'webpage') {
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
    // Step 4 (Static Site): Notification preference
    // -------------------------------------------------------------------------

    public function step4StaticSite(Request $request)
    {
        if ($request->session()->get('list_wizard.output_type') !== 'static_site') {
            return redirect()->route('lists.create.step1');
        }

        return view('media_platform.digest.content_sources.lists.wizard-step4-static-site');
    }

    public function step4StaticSiteSubmit(Request $request)
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
    // Step 5: Email notification preference (webpage only)
    // -------------------------------------------------------------------------

    public function step5(Request $request)
    {
        if ($request->session()->get('list_wizard.output_type') !== 'webpage') {
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

        if (($data['output_type'] ?? null) === 'webpage' && ! empty($data['output_destination_id'])) {
            $destination = OutputDestination::find($data['output_destination_id']);
        }

        return view('media_platform.digest.content_sources.lists.wizard-step6', compact('data', 'destination'));
    }

    public function step6Submit(Request $request)
    {
        if (! $request->session()->has('list_wizard.output_type')) {
            return redirect()->route('lists.create.step1');
        }

        $data       = $request->session()->get('list_wizard');
        $outputType = $data['output_type'];

        $list = ListModel::create([
            'user_id'               => auth()->id(),
            'name'                  => $data['name'],
            'description'           => $data['description'] ?? null,
            'timezone'              => $data['timezone'] ?? null,
            'enabled'               => true,
            'schedule_frequency'    => $data['schedule_frequency'],
            'schedule_day'          => $data['schedule_day'] ?? null,
            'schedule_time'         => $data['schedule_time'],
            'output_type'           => $outputType,
            'output_destination_id' => $outputType === 'webpage' ? ($data['output_destination_id'] ?? null) : null,
            'notify_by_email'       => in_array($outputType, ['webpage', 'static_site']) ? ($data['notify_by_email'] ?? false) : false,
            'retention_count'       => $request->input('retention_count') ?? $list->retention_count ?? 10,

        ]);

        $redirectTo = $data['redirect_to'] ?? null;

        $request->session()->forget('list_wizard');

        if ($redirectTo && \Illuminate\Support\Facades\Route::has($redirectTo)) {
            return redirect()->route($redirectTo)
                ->with('success', 'List created. Now assign your channel to it.');
        }

        return redirect()->route('lists.create.step7', ['list' => $list->id]);
    }

    // -------------------------------------------------------------------------
    // Step 7: Done
    // -------------------------------------------------------------------------

    public function step7(Request $request)
    {
        $list = null;

        if ($request->query('list')) {
            $list = ListModel::where('id', $request->query('list'))
                ->where('user_id', auth()->id())
                ->first();
        }

        return view('media_platform.digest.content_sources.lists.wizard-step7', compact('list'));
    }

    // -------------------------------------------------------------------------
    // Show
    // -------------------------------------------------------------------------

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

        // Load deploy hooks and published digests for static site lists
        $deployHooks      = null;
        $publishedDigests = null;

        if ($list->output_type === OutputType::StaticSite) {
            $deployHooks = $list->deployHooks()->orderBy('label')->get();
            $publishedDigests = $list->publishedDigests()
                ->orderByDesc('digest_date')
                ->limit($list->retention_count)
                ->get();
        }

        return view('media_platform.digest.content_sources.lists.show', compact(
            'list', 'sources', 'tracking', 'deployHooks', 'publishedDigests'
        ));
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
            'output_type'           => ['required', 'in:webpage,email,static_site'],
            'output_destination_id' => ['nullable', 'integer'],
            'notify_by_email'       => ['nullable', 'boolean'],
            'retention_count'       => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $outputType    = $request->input('output_type');
        $destinationId = null;

        if ($outputType === 'webpage' && $request->filled('output_destination_id')) {
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
            'output_destination_id' => $outputType === 'webpage' ? $destinationId : null,
            'notify_by_email'       => in_array($outputType, ['webpage', 'static_site']) ? $request->boolean('notify_by_email') : false,
            'retention_count'       => $outputType === 'static_site' ? ($request->input('retention_count') ?? 10) : $list->retention_count,
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