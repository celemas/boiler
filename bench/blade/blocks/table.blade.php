<table class="block-table">
    <thead>
        <tr>
            @foreach ($block['head'] as $cell)
                <th scope="col">{{ $cell }}</th>
            @endforeach
        </tr>
    </thead>
    <tbody>
        @foreach ($block['rows'] as $row)
            <tr>
                @foreach ($row as $cell)
                    <td>{{ $cell }}</td>
                @endforeach
            </tr>
        @endforeach
    </tbody>
</table>
