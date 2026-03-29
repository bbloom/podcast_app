<?php

namespace MediaPlatform\Tools\PhpServerlessProjectSponsors\Controllers;

use App\Http\Controllers\Controller;
use MediaPlatform\Tools\PhpServerlessProjectSponsors\Models\PhpServerlessProjectSponsor;
use MediaPlatform\Tools\PhpServerlessProjectSponsors\Requests\PhpServerlessProjectSponsorRequest;

class PhpServerlessProjectSponsorController extends Controller
{
    /**
     * Display all sponsors, ordered by full name.
     */
    public function index()
    {
        $sponsors = PhpServerlessProjectSponsor::orderBy('full_name')->get();

        return view('media_platform.tools.phpserverlessproject_sponsors.index', compact('sponsors'));
    }

    /**
     * Show the form for creating a new sponsor.
     */
    public function create()
    {
        return view('media_platform.tools.phpserverlessproject_sponsors.create');
    }

    /**
     * Persist a new sponsor record.
     */
    public function store(PhpServerlessProjectSponsorRequest $request)
    {
        PhpServerlessProjectSponsor::create($request->validated());

        return redirect()
            ->route('phpserverlessproject_sponsors.index')
            ->with('success', 'Sponsor created successfully.');
    }

    /**
     * Display a single sponsor record.
     */
    public function show(PhpServerlessProjectSponsor $phpserverlessproject_sponsor)
    {
        return view(
            'media_platform.tools.phpserverlessproject_sponsors.show',
            ['sponsor' => $phpserverlessproject_sponsor]
        );
    }

    /**
     * Show the form for editing an existing sponsor record.
     */
    public function edit(PhpServerlessProjectSponsor $phpserverlessproject_sponsor)
    {
        return view(
            'media_platform.tools.phpserverlessproject_sponsors.edit',
            ['sponsor' => $phpserverlessproject_sponsor]
        );
    }

    /**
     * Persist updates to an existing sponsor record.
     */
    public function update(PhpServerlessProjectSponsorRequest $request, PhpServerlessProjectSponsor $phpserverlessproject_sponsor)
    {
        $phpserverlessproject_sponsor->update($request->validated());

        return redirect()
            ->route('phpserverlessproject_sponsors.index')
            ->with('success', 'Sponsor updated successfully.');
    }

    /**
     * Show the delete confirmation page for a sponsor record.
     */
    public function deleteConfirm(PhpServerlessProjectSponsor $phpserverlessproject_sponsor)
    {
        return view(
            'media_platform.tools.phpserverlessproject_sponsors.delete_confirm',
            ['sponsor' => $phpserverlessproject_sponsor]
        );
    }

    /**
     * Delete a sponsor record.
     */
    public function destroy(PhpServerlessProjectSponsor $phpserverlessproject_sponsor)
    {
        $phpserverlessproject_sponsor->delete();

        return redirect()
            ->route('phpserverlessproject_sponsors.index')
            ->with('success', 'Sponsor deleted successfully.');
    }
}