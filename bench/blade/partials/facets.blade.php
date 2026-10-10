<form class="facets" action="{{ $category['url'] }}">
    @foreach ($facets as $facet)
        <fieldset class="facet facet-{{ $facet['key'] }}">
            <legend>{{ $facet['title'] }}</legend>
            @if ($facet['expanded'])
                <ul>
                    @foreach ($facet['options'] as $option)
                        <li>
                            <label>
                                <input type="checkbox" name="{{ $facet['key'] }}[]" value="{{ $option['value'] }}" @checked($option['selected'])>
                                {{ $option['label'] }}
                                <small>({{ $option['count'] }})</small>
                            </label>
                        </li>
                    @endforeach
                </ul>
            @else
                <p class="facet-summary">{{ count($facet['options']) }} options</p>
            @endif
        </fieldset>
    @endforeach
</form>
