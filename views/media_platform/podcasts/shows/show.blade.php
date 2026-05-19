<x-layouts.app title="{{ $show->title }}">

    {{-- Breadcrumb + heading --}}
    <div class="mb-8">
        <div class="flex items-center gap-2 text-sm text-gray-500 mb-6">
            <a href="{{ route('podcast_shows.index') }}" class="hover:text-purple-700 transition">← Podcast Shows</a>
            <span>›</span>
            <span class="text-gray-700">{{ $show->title }}</span>
        </div>
        <div class="flex items-center justify-between">
            <h1 class="text-2xl font-bold text-gray-800">{{ $show->title }}</h1>
            <a href="{{ route('podcast_shows.edit', $show) }}"
               class="bg-purple-700 hover:bg-purple-800 text-white text-sm font-semibold px-5 py-2.5 rounded-lg transition">
                Edit
            </a>
        </div>
    </div>

    @session('success')
        <div class="bg-green-50 border border-green-300 rounded-lg px-4 py-3 mb-6 text-sm text-green-800">
            {{ $value }}
        </div>
    @endsession

    @session('error')
        <div class="bg-red-50 border border-red-300 rounded-lg px-4 py-3 mb-6 text-sm text-red-800">
            {{ $value }}
        </div>
    @endsession

    {{-- Show details --}}
    <div class="border border-purple-500 rounded-lg p-6 mb-8">
        <div class="text-xl font-bold text-gray-800 mb-1 flex items-end gap-3"">
            <img 
                alt="{{  $show->title }} "
                class="h-[100px] w-[100px] object-cover border border-gray-200"
                src="{{ $show->itunes_image }}" 
            />
            
        </div>
        <p class="text-lg text-black mb-4 mt-4">{{ $show->description }}</p>

        <table class="text-sm text-gray-600 border-collapse w-full">
          <tbody class="divide-y divide-gray-300">

            {{-- Core --}}
            <tr><td colspan="2" class="pt-4 pb-1 text-xs font-semibold text-purple-700 uppercase tracking-wider">Core</td></tr>
            <tr>
                <td class="pr-6 py-1 text-gray-500 whitespace-nowrap align-top w-48">ID</td>
                <td class="py-1 text-gray-800 font-mono text-xs">{{ $show->id }}</td>
            </tr>
            <tr>
                <td class="pr-6 py-1 text-gray-500 whitespace-nowrap align-top w-48">Slug</td>
                <td class="py-1 text-gray-800 font-mono text-xs">{{ $show->slug }}</td>
            </tr>
            <tr>
                <td class="pr-6 py-1 text-gray-500 whitespace-nowrap align-top w-48">Description</td>
                <td class="py-1 text-gray-800 font-mono text-xs">{{ $show->description }}</td>
            </tr>
            <tr>
                <td class="pr-6 py-1 text-gray-500 whitespace-nowrap align-top">RSS Link</td>
                <td class="py-1"><a href="{{ $show->rss_link }}" class="text-purple-700 hover:underline text-xs" target="_blank">{{ $show->rss_link ?? '—'}}</a></td>
            </tr>


            {{-- iTunes --}}
            <tr><td colspan="2" class="pt-6 pb-1 text-xs font-semibold text-purple-700 uppercase tracking-wider">iTunes</td></tr>
            <tr>
                <td class="pr-6 py-1 text-gray-500 whitespace-nowrap align-top">Image</td>
                <td class="py-1 text-gray-800">
                    <a 
                        href="{{ $show->itunes_image }}" 
                        class="text-purple-700 hover:underline text-xs" 
                        target="_blank"
                    >
                        {{ $show->itunes_image ?? '—' }}
                    </a>
                </td>
            </tr>
            <tr>
                <td class="pr-6 py-1 text-gray-500 whitespace-nowrap align-top">Language</td>
                <td class="py-1 text-gray-800">{{ $show->itunes_language ?? '—' }}</td>
            </tr>
            <tr>
                <td class="pr-6 py-1 text-gray-500 whitespace-nowrap align-top">Primary Category</td>
                <td class="py-1 text-gray-800">{{ $show->itunes_category_primary ?? '—' }}</td>
            </tr>
            <tr>
                <td class="pr-6 py-1 text-gray-500 whitespace-nowrap align-top">Secondary Cat (optional)</td>
                <td class="py-1 text-gray-800">{{ $show->itunes_category_secondary ?? '—' }}</td>
            </tr>
            <tr>
                <td class="pr-6 py-1 text-gray-500 whitespace-nowrap align-top">Explicit</td>
                <td class="py-1 text-gray-800">{{ $show->itunes_explicit ? 'Yes' : 'No' }}</td>
            </tr>
            <tr>
                <td class="pr-6 py-1 text-gray-500 whitespace-nowrap align-top">Author</td>
                <td class="py-1 text-gray-800">{{ $show->itunes_author ?? '—' }}</td>
            </tr>
            <tr>
                <td class="pr-6 py-1 text-gray-500 whitespace-nowrap align-top">Link</td>
                <td class="py-1 text-gray-800">{{ $show->itunes_link ?? '—' }}</td>
            </tr>
            <tr>
                <td class="pr-6 py-1 text-gray-500 whitespace-nowrap align-top">Email</td>
                <td class="py-1 text-gray-800">{{ $show->itunes_email ?? '—' }}</td>
            </tr>
            <tr>
                <td class="pr-6 py-1 text-gray-500 whitespace-nowrap align-top">Name</td>
                <td class="py-1 text-gray-800">{{ $show->itunes_name ?? '—' }}</td>
            </tr>
            <tr>
                <td class="pr-6 py-1 text-gray-500 whitespace-nowrap align-top">Title</td>
                <td class="py-1 text-gray-800">{{ $show->itunes_title ?? '—' }}</td>
            </tr>
            <tr>
                <td class="pr-6 py-1 text-gray-500 whitespace-nowrap align-top">Type</td>
                <td class="py-1 text-gray-800">{{ $show->itunes_type ?? '—' }}</td>
            </tr>
            <tr>
                <td class="pr-6 py-1 text-gray-500 whitespace-nowrap align-top">Copyright</td>
                <td class="py-1 text-gray-800">{{ $show->itunes_copyright ?? '—' }}</td>
            </tr>
            <tr>
                <td class="pr-6 py-1 text-gray-500 whitespace-nowrap align-top">New Feed URL</td>
                <td class="py-1 text-gray-800">{{ $show->itunes_new_feed_url ?? 'n/a' }}</td>
            </tr>
            <tr>
                <td class="pr-6 py-1 text-gray-500 whitespace-nowrap align-top">Block</td>
                <td class="py-1 text-gray-800">{{ $show->itunes_block ? 'Yes' : 'No' }}</td>
            </tr>
            <tr>
                <td class="pr-6 py-1 text-gray-500 whitespace-nowrap align-top">Complete</td>
                <td class="py-1 text-gray-800">{{ $show->itunes_complete ? 'Yes' : 'No' }}</td>
            </tr>
            <tr>
                <td class="pr-6 py-1 text-gray-500 whitespace-nowrap align-top">Summary</td>
                <td class="py-1 text-gray-800">{{ $show->itunes_summary ?? '—' }}</td>
            </tr>
            <tr>
                <td class="pr-6 py-1 text-gray-500 whitespace-nowrap align-top">Content Encoded</td>
                <td class="py-1 text-gray-800">{{ $show->itunes_content_encoded ?? '—' }}</td>
            </tr>
            
            
            {{-- Spotify --}}
            <tr><td colspan="2" class="pt-6 pb-1 text-xs font-semibold text-purple-700 uppercase tracking-wider">Spotify</td></tr>
            <tr>
                <td class="pr-6 py-1 text-gray-500 whitespace-nowrap align-top">Episode Limit</td>
                <td class="py-1 text-gray-800">{{ $show->spotify_limit === 0 ? 'No limit' : $show->spotify_limit }}</td>
            </tr>
            <tr>
                <td class="pr-6 py-1 text-gray-500 whitespace-nowrap align-top">Country of Origin</td>
                <td class="py-1 text-gray-800">{{ $show->spotify_country_of_origin }}</td>
            </tr>

            {{-- Website --}}
            <tr><td colspan="2" class="pt-6 pb-1 text-xs font-semibold text-purple-700 uppercase tracking-wider">Website</td></tr>
            <tr>
                <td class="pr-6 py-1 text-gray-500 whitespace-nowrap align-top">Content</td>
                <td class="py-1 text-gray-800">{{ $show->website_content ?? '—' }}</td>
            </tr>
            <tr>
                <td class="pr-6 py-1 text-gray-500 whitespace-nowrap align-top">Excerpt</td>
                <td class="py-1 text-gray-800">{{ $show->website_excerpt ?? '—' }}</td>
            </tr>
            <tr>
                <td class="pr-6 py-1 text-gray-500 whitespace-nowrap align-top">Meta Description</td>
                <td class="py-1 text-gray-800">{{ $show->website_meta_description ?? '—' }}</td>
            </tr>
            <tr>
                <td class="pr-6 py-1 text-gray-500 whitespace-nowrap align-top">Publish On</td>
                <td class="py-1 text-gray-800">{{ $show->website_publish_on?->format('d M Y') ?? '—' }}</td>
            </tr>
            <tr>
                <td class="pr-6 py-1 text-gray-500 whitespace-nowrap align-top">Featured Image</td>
                <td class="py-1 text-gray-800">
                    @if ($show->website_featured_image)
                        <a 
                            href="{{ $show->website_featured_image }}" 
                            class="text-purple-700 hover:underline text-xs" 
                            target="_blank"
                        >
                            {{ $show->website_featured_image ?? '—' }}
                        </a>
                    @else 
                    —
                    @endif
                </td>
            </tr>
            <tr>
                <td class="pr-6 py-1 text-gray-500 whitespace-nowrap align-top">Publish On</td>
                <td class="py-1 text-gray-800">{{ $show->website_publish_on?->format('d M Y') ?? '—' }}</td>
            </tr>
            <tr>
                <td class="pr-6 py-1 text-gray-500 whitespace-nowrap align-top">Visible</td>
                <td class="py-1">
                    @if ($show->website_enabled)
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800">Enabled</span>
                    @else
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-600">Disabled</span>
                    @endif
                </td>
            </tr>
            

            {{-- Meta --}}
            <tr><td colspan="2" class="pt-6 pb-1 text-xs font-semibold text-purple-700 uppercase tracking-wider">Record</td></tr>
            <tr>
                <td class="pr-6 py-1 text-gray-500 whitespace-nowrap align-top">Created</td>
                <td class="py-1 text-gray-800">{{ $show->created_at->format('d M Y') }}</td>
            </tr>
            <tr>
                <td class="pr-6 py-1 text-gray-500 whitespace-nowrap align-top">Updated</td>
                <td class="py-1 text-gray-800">{{ $show->updated_at->format('d M Y') }}</td>
            </tr>
          </tbody>  
        </table>
    </div>


    <hr class="my-4 border-t-2 border-purple-200">

    {{-- Episodes --}}
    <div class="flex items-center justify-between mb-4">
        <h2 class="text-xl font-bold text-purple-700">
            Episodes
            <span class="ml-1 text-sm font-normal text-gray-400">({{ $show->episodes()->count() }})</span>
        </h2>

    </div>    

    @if ($episodes->isEmpty())
        <div class="border border-gray-200 rounded-lg px-6 py-10 text-center text-sm text-gray-400">
            No episodes yet for this show.
        </div>
    @else
        <div class="border border-gray-200 rounded-lg overflow-hidden">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">Title</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">Scheduled</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">RSS</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">Website</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach ($episodes as $episode)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4">
                                <a href="{{ route('podcast_episodes.show', $episode) }}"
                                   class="font-medium text-purple-700 hover:underline">
                                    {{ $episode->title }}
                                </a>
                            </td>
                            <td class="px-6 py-4 text-gray-600">{{ $episode->status?->label() ?? '—' }}</td>
                            <td class="px-6 py-4 text-gray-500">{{ $episode->scheduled_date?->format('d M Y') ?? '—' }}</td>
                            <td class="px-6 py-4">
                                @if ($episode->rss_feed_enabled)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800">On</span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-500">Off</span>
                                @endif
                            </td>
                            <td class="px-6 py-4">
                                @if ($episode->website_enabled)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800">On</span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-500">Off</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @if ($episodes->hasPages())
            <div class="mt-4">{{ $episodes->links() }}</div>
        @endif
    @endif


    <hr class="my-4 border-t-2 border-purple-200">

    {{-- Footer Links --}}
    <div class="mt-8">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-xl font-bold text-purple-700">Footer Links</h2>
            <a href="{{ route('footer_links.create', ['podcast_show_id' => $show->id]) }}"
            class="inline-flex items-center px-3 py-1.5 bg-purple-700 text-white text-sm font-medium rounded hover:bg-purple-800">
                Add Footer Link
            </a>
        </div>

        @if ($footerLinks->isEmpty())
            <p class="text-sm text-gray-500">No footer links for this show.</p>
        @else
            <div class="bg-white shadow rounded-lg overflow-hidden">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Order</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">URL</th>
                            <th class="px-6 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @foreach ($footerLinks as $link)
                            <tr>
                                <td class="px-6 py-4 text-sm text-gray-900">{{ $link->link_order }}</td>
                                <td class="px-6 py-4 text-sm text-gray-900">
                                    <a href="{{ route('footer_links.show', $link) }}" class="text-purple-700 hover:underline">
                                        {{ $link->link_name }}
                                    </a>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-500 truncate max-w-xs">{{ $link->link_url }}</td>
                                <td class="px-6 py-4 text-right text-sm">
                                    <a href="{{ route('footer_links.show', $link) }}" class="text-purple-700 hover:underline">Details</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
    

    <hr class="my-4 border-t-2 border-purple-200">


    {{-- Static Site Builds --}}
    <div class="pb-1 text-xl font-bold text-purple-700 tracking-wider mt-8">Static Site Builds</div>
    <div class="border border-purple-500 rounded-lg px-6 py-4 mb-8">
        <p class="text-sm text-gray-600 mb-4">
            Trigger a static site build to push the latest published episodes to your front-end site(s).
        </p>
        <a href="{{ route('post_production.trigger_builds.select', $show) }}"
           class="inline-block bg-blue-700 hover:bg-blue-800 text-white text-sm font-semibold px-5 py-2.5 rounded-lg transition">
            Trigger Static Site Builds
        </a>
    </div>

    <div class="mt-6 text-sm">
        <a href="{{ route('podcast_shows.index') }}" class="hover:text-purple-700 transition">← Podcast Shows</a>
    </div>

</x-layouts.app>