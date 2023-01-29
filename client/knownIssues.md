
# Issues

- Media keys are pretty buggy. Playing a song from an album causes some weird issues, especially if there is only 1 song in the album.
- Pagination support is needed, my collection is small so the issue is hardly noticed. But on a larger library, the app will likely freeze for some time on heavy renders.
- When the instance is open a for a while, and the user auth expires, the app gets stuck until you refresh a few times.
- Poor naming convention, `ID` vs `Id`
- Styles are messy
- In prod builds the SVG images are smaller
- 
# Nice to have, maybe

- Switch to tailwind from Stylus? Lot of work, low priority. Potentially, only do new components in tailwind and slowly convert.
- Store cache on local storage? Can make things feel a lot more snappy

# TODOs
- Better test states where there are no songs/albums/playlists/artists