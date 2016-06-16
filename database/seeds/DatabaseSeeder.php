<?php

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Model::unguard();
        $this->call('UserTableSeeder');
        $this->call('CatalogTableSeeder');
        $this->call('ArtistTableSeeder');
        $this->call('AlbumTableSeeder');
        $this->call('LicenseTableSeeder');
        $this->call('SongTableSeeder');
        $this->call('LiveStreamTableSeeder');
        $this->call('ClipTableSeeder');
        $this->call('MovieTableSeeder');
        $this->call('PersonalVideoTableSeeder');
        $this->call('TvshowTableSeeder');
        $this->call('TvshowSeasonTableSeeder');
        $this->call('TvshowEpisodeTableSeeder');
        $this->call('PodcastTableSeeder');
        $this->call('PodcastEpisodeTableSeeder');
        $this->call('PlaylistTableSeeder');
        $this->call('PlaylistItemTableSeeder');
        $this->call('TmpPlaylistTableSeeder');
        $this->call('FavoriteTableSeeder');
        $this->call('ShareTableSeeder');
        $this->call('RatingTableSeeder');
        $this->call('ChannelTableSeeder');
        $this->call('BookmarkTableSeeder');
        $this->call('BroadcastTableSeeder');
        $this->call('LabelTableSeeder');
        $this->call('LabelMapTableSeeder');
        $this->call('WantedTableSeeder');
        $this->call('ShoutTableSeeder');
        $this->call('PrivateMsgTableSeeder');
        $this->call('UserActivityTableSeeder');
        $this->call('UserFollowerTableSeeder');
        $this->call('ImageTableSeeder');
        Model::reguard();
    }
}
