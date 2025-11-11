<table>
    <thead>
        <tr>
            <th>ID Staff</th>
            <th>Nama Staff</th>
            <th>Periode</th>
            <th>Situs</th>
            <th>Status Tidak Bersalah</th>
            <th>Status Bersalah Low</th>
            <th>Status Bersalah Medium</th>
            <th>Status Bersalah High</th>
            <th>Total Case</th>
        </tr>
    </thead>
    <tbody>
        @foreach($data as $item)
            <tr>
                <td>{{ $item->id_staff }}</td>
                <td>{{ $item->nama_staff }}</td>
                <td>{{ $item->periode }}</td>
                <td>{{ $item->site_situs }}</td>
                <td>{{ $item->tidak_bersalah }}</td>
                <td>{{ $item->bersalah_low }}</td>
                <td>{{ $item->bersalah_medium }}</td>
                <td>{{ $item->bersalah_high }}</td>
                <td>{{ $item->total_case }}</td>
            </tr>
        @endforeach
    </tbody>
</table>
