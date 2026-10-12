param([switch]$Supplement)
$ErrorActionPreference = 'Stop'
$slugs = @(
    'scotty-giai-cuu-hoang-thuong', 'than-su-chung-quy-linh-gioi-dai-chien', 'quyet-cua-anh-nay',
    'trai-tim-quai-thu', 'blue-lock-cuoc-chien-cua-nhung-tien-dao', 'an-mang-xem-hoan-hao',
    'nguoi-me-khac', 'shaun-the-sheep-quai-la-ghe-nha', 'quy-an-tang-4-ho-tinh', 'me-min',
    'an-mang-karaoke-1', 'chi-chi-em-em-3', 'tran-yem', 'street-fighter',
    'bo-sua-bay', 'chuyen-di-nho-doi', 'nguoi-duoc-chon', 'ngay-con-con-me',
    'trai-giam-hanh-phuc', 'focker-in-law', 'con-mat-thu-ba', 'avengers-doomsday',
    'dune-part-three', 'godzilla-minus-zero', 'the-cat-in-the-hat', 'huyet-thong', 'nguoi-me-xau'
)
$records = @()
if ($Supplement) {
    $records = Get-Content (Join-Path $PSScriptRoot 'candidates.json') -Raw -Encoding UTF8 | ConvertFrom-Json
    while ($records.Count -eq 1 -and $records[0] -is [array]) { $records = $records[0] }
    $records = @($records)
    $slugs = @('godzilla-tru-khong')
}
foreach ($slug in $slugs) {
    $url = "https://moveek.com/phim/$slug/"
    try {
        $response = Invoke-WebRequest -Uri $url -UseBasicParsing -TimeoutSec 25
        $html = $response.Content
        $movie = $null
        foreach ($match in [regex]::Matches($html, '(?s)<script type="application/ld\+json">(.*?)</script>')) {
            $data = $match.Groups[1].Value | ConvertFrom-Json
            if ($data.'@type' -eq 'Movie') { $movie = $data; break }
        }
        if (!$movie) { throw 'Movie metadata not found' }
        $trailer = [regex]::Match($html, 'data-video-url="([A-Za-z0-9_-]{11})"').Groups[1].Value
        $original = [regex]::Match($html, '(?s)<p class="mb-0 text-muted text-truncate">(.*?)</p>').Groups[1].Value
        $original = [System.Net.WebUtility]::HtmlDecode(([regex]::Replace($original, '<[^>]+>', '') -replace '\s+', ' ').Trim())
        $record = [ordered]@{
            slug = $slug; source = $url; title = $movie.name; original_and_genres = $original
            release_date_vn = $movie.datePublished; duration = $movie.duration
            director = @($movie.director | ForEach-Object { $_.name }); actors = @($movie.actor | ForEach-Object { $_.name })
            genres = $movie.genre; source_synopsis = $movie.description; poster_source = $movie.image.url
            trailer_id = $trailer; researched_at = '2026-10-12'; trailer_metadata = $null
        }
        $records += [pscustomobject]$record
        Write-Output "$slug | $($movie.datePublished) | $($movie.duration) | trailer=$trailer"
    } catch { Write-Output "$slug | ERROR: $($_.Exception.Message)" }
    ConvertTo-Json -InputObject @($records) -Depth 12 | Set-Content -LiteralPath (Join-Path $PSScriptRoot 'candidates.json') -Encoding UTF8
}
