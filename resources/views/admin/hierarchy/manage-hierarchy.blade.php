<!-- filepath: resources/views/manage-hierarchy.blade.php -->
<x-admin-layout>


<div class="container">
    <h1>Manage Hierarchy</h1>

    @if(isset($hierarchyData['squads']))
        <h2>Squads</h2>
        <ul>
            @foreach($hierarchyData['squads'] as $squad)
                <li>
                    {{ $squad->name }} (Leader: {{ $squad->leader->name ?? 'None' }})
                    <ul>
                        @if(isset($squad->batches))
                            @foreach($squad->batches as $batch)
                                <li>
                                    {{ $batch->name }} (Leader: {{ $batch->leader->name ?? 'None' }})
                                    <ul>
                                        @if(isset($batch->teams))
                                            @foreach($batch->teams as $team)
                                                <li>
                                                    {{ $team->name }} (Leader: {{ $team->leader->name ?? 'None' }})
                                                    <ul>
                                                        @if(isset($team->members))
                                                            @foreach($team->members as $member)
                                                                <li>{{ $member->name }}</li>
                                                            @endforeach
                                                        @endif
                                                    </ul>
                                                </li>
                                            @endforeach
                                        @endif
                                    </ul>
                                </li>
                            @endforeach
                        @endif
                    </ul>
                </li>
            @endforeach
        </ul>
    @elseif(isset($hierarchyData['batches']))
        <h2>Batches</h2>
        <ul>
            @foreach($hierarchyData['batches'] as $batch)
                <li>
                    {{ $batch->name }} (Leader: {{ $batch->leader->name ?? 'None' }})
                    <ul>
                        @if(isset($batch->teams))
                            @foreach($batch->teams as $team)
                                <li>
                                    {{ $team->name }} (Leader: {{ $team->leader->name ?? 'None' }})
                                    <ul>
                                        @if(isset($team->members))
                                            @foreach($team->members as $member)
                                                <li>{{ $member->name }}</li>
                                            @endforeach
                                                        @endif
                                                    </ul>
                                                </li>
                                            @endforeach
                                        @endif
                                    </ul>
                                </li>
                            @endforeach
                        </ul>
    @elseif(isset($hierarchyData['teams']))
        <h2>Teams</h2>
        <ul>
            @foreach($hierarchyData['teams'] as $team)
                <li>
                    {{ $team->name }} (Leader: {{ $team->leader->name ?? 'None' }})
                    <ul>
                        @if(isset($team->members))
                            @foreach($team->members as $member)
                                <li>{{ $member->name }}</li>
                            @endforeach
                        @endif
                    </ul>
                </li>
            @endforeach
        </ul>
    @else
        <p>No hierarchy data available.</p>
    @endif
</div>


</x-admin-layout>
<!-- This is a Blade template for managing hierarchy in a web application. It displays the hierarchy of squads, batches, teams, and members. -->