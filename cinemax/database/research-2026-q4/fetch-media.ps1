$ErrorActionPreference = 'Stop'
$selected = @('scotty-giai-cuu-hoang-thuong','than-su-chung-quy-linh-gioi-dai-chien','quyet-cua-anh-nay','trai-tim-quai-thu','blue-lock-cuoc-chien-cua-nhung-tien-dao','an-mang-xem-hoan-hao','nguoi-me-khac','shaun-the-sheep-quai-la-ghe-nha','quy-an-tang-4-ho-tinh','an-mang-karaoke-1','bo-sua-bay','chuyen-di-nho-doi','ngay-con-con-me','trai-giam-hanh-phuc','focker-in-law','con-mat-thu-ba','avengers-doomsday','dune-part-three','godzilla-tru-khong','nguoi-me-xau')
$candidates = Get-Content (Join-Path $PSScriptRoot 'candidates.json') -Raw -Encoding UTF8 | ConvertFrom-Json
function Expand-MovieRecords($items) {
    foreach ($item in $items) {
        if ($item.slug) { $item }
        elseif ($item.value) { Expand-MovieRecords $item.value }
        elseif ($item -is [array]) { Expand-MovieRecords $item }
    }
}
$candidates = @(Expand-MovieRecords $candidates)
$records = @($candidates | Where-Object { $_.slug -in $selected })
$officialTrailers = @{
    'scotty-giai-cuu-hoang-thuong' = 'B6z2QdwrJ8A'
    'trai-tim-quai-thu' = '_1F2MrfQLjo'
    'shaun-the-sheep-quai-la-ghe-nha' = 'UZ46Smi6mOw'
    'quy-an-tang-4-ho-tinh' = '0t29-MGiths'
    'an-mang-karaoke-1' = 'UOjRGIatX38'
    'bo-sua-bay' = '-UgQeJmtYNg'
    'chuyen-di-nho-doi' = 'WZHzF3ofm4o'
    'focker-in-law' = 'vyISuWUWcFs'
    'con-mat-thu-ba' = 'ajspTYhCWfo'
    'nguoi-me-xau' = 'uCw12aOSHXE'
}
$root = [System.IO.Path]::GetFullPath((Join-Path $PSScriptRoot '../../public/'))
foreach ($record in $records) {
    if ($officialTrailers.ContainsKey($record.slug)) { $record.trailer_id = $officialTrailers[$record.slug]; $record.trailer_metadata = $null }
    try {
        if ($record.poster_source -notmatch '^https://cdn\.moveek\.com/storage/media/cache/tall/[a-zA-Z0-9]+\.webp$') { throw 'Not a movie poster URL' }
        $relative = "assets/uploads/movies/$($record.slug)-2026.webp"
        if (!(Test-Path -LiteralPath (Join-Path $root $relative))) {
            Invoke-WebRequest -Uri $record.poster_source -UseBasicParsing -TimeoutSec 25 -OutFile (Join-Path $root $relative)
        }
        $record | Add-Member -NotePropertyName poster_url -NotePropertyValue $relative -Force
        Write-Output "POSTER $($record.slug)"
    } catch { Write-Output "POSTER ERROR $($record.slug): $($_.Exception.Message)" }
    if ($record.trailer_id) {
        try {
            $url = 'https://www.youtube.com/oembed?url=' + [uri]::EscapeDataString("https://www.youtube.com/watch?v=$($record.trailer_id)") + '&format=json'
            $record.trailer_metadata = Invoke-RestMethod -Uri $url -TimeoutSec 20
            Write-Output "TRAILER $($record.slug): $($record.trailer_metadata.title) | $($record.trailer_metadata.author_name)"
        } catch { Write-Output "TRAILER ERROR $($record.slug): $($_.Exception.Message)" }
    }
}
ConvertTo-Json -InputObject @($records) -Depth 12 | Set-Content -LiteralPath (Join-Path $PSScriptRoot 'selected.json') -Encoding UTF8
