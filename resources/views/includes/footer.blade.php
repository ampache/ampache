@if (Config::get('view.show_donate'))
    <a id="donate" href="//ampache.github.io/donate.html" title="Donate" target="_blank">.:: {{ T_('Donate') }} ::. </a> |
@endif
@if (Config::get('view.custom_text_footer'))
    {{ Config::get('view.custom_text_footer') }}
@else
    <a id="ampache_link" href="https://github.com/ampache/ampache#readme" target="_blank" title="Copyright © 2001 - 2016 Ampache.org">Ampache</a>
@endif
