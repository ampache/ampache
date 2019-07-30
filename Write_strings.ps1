$FileList = Get-ChildItem -Path '.\' -Recurse -File

$StringList = $FileList | ForEach-Object {
  $_ | Get-Content | Select-String -Pattern 'n?T_\([''"](.+?)[''"]\)' -AllMatches
}

$Strings = $StringList.Matches | ForEach-Object {
  $_.Groups[1].Value
} | Sort-Object -Unique

$Strings | Out-File -FilePath "test.txt" -Encoding utf8