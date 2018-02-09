<div id="sb_Subsearch">
    <form name="search" method="post" action="{{ url('search.php?type=song') }}" enctype="multipart/form-data" style="Display:inline">
        <input type="text" name="rule_1_input" id="searchString" placeholder="{{ 'Search...' }}" />
        <input type="hidden" name="action" value="search" />
        <input type="hidden" name="rule_1_operator" value="0" />
        <input type="hidden" name="object_type" value="song" />
        <select name="rule_1" id="searchStringRule">
            <option value="anywhere">{{ 'Anywhere' }}</option>
            <option value="title">{{ 'Title' }}</option>
            <option value="album">{{ 'Album' }}</option>
            <option value="artist">{{ 'Artist' }}</option>
            <option value="playlist_name">{{ 'Playlist' }}</option>
            <option value="tag">{{ 'Tag' }}</option>
            @if (Config::get('feature.label'))
                <option value="label">{{ 'Label' }}</option>
            @endif
            @if (Config::get('feature.wanted'))
                <option value="missing_artist">{{ 'Missing Artist' }}</option>
            @endif
        </select>
        <input class="button" type="submit" value="{{ 'Search' }}" id="searchBtn" />
        <a href="{{ url('search.php?type=song') }}" class="button" id="advSearchBtn">{{ 'Advanced Search' }}</a>
    </form>
</div>