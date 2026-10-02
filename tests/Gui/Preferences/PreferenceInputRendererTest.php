<?php

declare(strict_types=1);

/**
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright Ampache.org, 2001-2026
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 */

namespace Ampache\Gui\Preferences;

use Ampache\Config\AmpConfig;
use Ampache\PluginFactoryMockTrait;
use Ampache\Repository\Model\User;
use Ampache\Repository\UpdateInfoRepositoryInterface;
use DI\Container;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class PreferenceInputRendererTest extends TestCase
{
    use PluginFactoryMockTrait;

    private PreferenceInputRenderer $subject;

    public function testABitrateFieldKeepsItsUnitStepAndFloor(): void
    {
        $html = $this->render($this->item(name: 'transcode_bitrate', value: '128000', shippedDefault: '128000'));

        $this->assertStringContainsString('min="0"', $html);
        $this->assertStringContainsString('step="1000"', $html);
        $this->assertStringContainsString('bps', $html);
    }

    public function testABooleanIsAnOnOffSelectAndNotACheckbox(): void
    {
        $html = $this->render($this->item(name: 'download', type: PreferenceType::BOOLEAN, value: '1'));

        $this->assertStringContainsString('<select', $html);
        $this->assertStringNotContainsString('type="checkbox"', $html);
        $this->assertStringContainsString('<option value="1" selected="selected">', $html);
        $this->assertStringContainsString('<option value="0">', $html);
    }

    public function testAClosedSetRendersItsLabelsNotItsValues(): void
    {
        $html = $this->render($this->item(
            name: 'transcode',
            type: PreferenceType::STRING,
            value: 'default',
            choices: ['never' => 'Never', 'default' => 'Default', 'always' => 'Always'],
        ));

        $this->assertStringContainsString('<option value="never">Never</option>', $html);
        $this->assertStringContainsString('<option value="default" selected="selected">Default</option>', $html);
    }

    public function testAControlCarriesTheServerValueSoItCanBeCopiedBack(): void
    {
        $item = new PreferenceItem(
            name: 'custom_blankalbum',
            description: 'd',
            type: PreferenceType::STRING,
            subcategory: null,
            level: 25,
            value: '',
            shippedDefault: '',
            systemValue: 'https://play.dogmazic.net/blank.png',
            choices: null,
            editable: true,
            isSecret: false,
            secretIsSet: false,
            help: null,
        );

        $this->assertStringContainsString('data-server="https://play.dogmazic.net/blank.png"', $this->render($item));
    }

    public function testAFieldRenderedBlankIsNotBornAlreadyModified(): void
    {
        $html = $this->render($this->item(name: 'transcode_bitrate_api', value: '0', shippedDefault: '0'));

        $this->assertStringContainsString('value=""', $html);
        $this->assertStringContainsString('data-initial=""', $html, 'a blank field must not read as changed on load');
    }

    /**
     * The server said "same as the default" while the page said "changed": one of them had to be wrong.
     */
    public function testAFieldShownBlankAlsoCallsItsDefaultBlank(): void
    {
        $html = $this->render($this->item(name: 'transcode_bitrate_api', value: '0', shippedDefault: '0'));

        $this->assertStringContainsString('data-default=""', $html, 'otherwise the row is marked changed for ever');
        $this->assertStringContainsString('data-initial=""', $html);
    }

    public function testAFieldTheUserMayNotChangeOffersNoInputAtAll(): void
    {
        $html = $this->render($this->item(name: 'download', type: PreferenceType::BOOLEAN, value: '1', editable: false));

        $this->assertStringNotContainsString('<input', $html);
        $this->assertStringNotContainsString('<select', $html);
        $this->assertStringContainsString($this->subject->label($this->item(name: 'download', type: PreferenceType::BOOLEAN, value: '1'), '1'), $html);
    }

    public function testAFreeTextPreferenceKeepsItsValue(): void
    {
        $item = $this->item(name: 'site_title', type: PreferenceType::STRING, value: 'Dogmazic');

        $this->assertSame('Dogmazic', $this->subject->label($item, 'Dogmazic'));
    }

    /**
     * Without the token in the callback a forged link would bind this account to someone else's service.
     */
    public function testAGrantLinkCarriesASingleUseTokenInItsCallback(): void
    {
        $html = $this->renderGrantLink(challenge: '');

        $this->assertStringContainsString('class="pref-grant"', $html);
        $this->assertMatchesRegularExpression('/form_validation%3D[0-9a-f]{8}/', $html);
    }

    public function testAGrantLinkIsNeverOfferedWhileEditingSomeoneElse(): void
    {
        $html = $this->render($this->item(name: 'lastfm_grant_link', type: PreferenceType::STRING, value: ''), isSelf: false);

        $this->assertStringNotContainsString('<a ', $html);
        $this->assertStringNotContainsString('<input', $html);
    }

    /**
     * `editable` carries both the level and the demo-mode lock, and this link leaves for a third party.
     */
    public function testAGrantLinkIsNotOfferedOnALockedRow(): void
    {
        $html = $this->render($this->item(name: 'lastfm_grant_link', type: PreferenceType::STRING, value: '', editable: false));

        $this->assertStringNotContainsString('pref-grant', $html);
        $this->assertStringContainsString('pref-readonly', $html);
    }

    public function testAGrantLinkIsNotOfferedWithoutAnApiKey(): void
    {
        $this->assertSame('', $this->renderGrantLink(challenge: '', apiKey: ''));
    }

    /**
     * `load()` fails on an empty challenge, which is the very state the link exists to leave.
     */
    public function testAGrantLinkIsOfferedBeforeTheFirstAuthorisation(): void
    {
        $this->assertStringContainsString('class="pref-grant"', $this->renderGrantLink(challenge: ''));
        $this->assertStringContainsString('class="pref-grant"', $this->renderGrantLink(challenge: 'already-granted'));
    }

    public function testALabelCarryingAnEntityIsReturnedAsTheCharacterItNames(): void
    {
        $item = $this->item(name: 'lang', type: PreferenceType::SPECIAL, value: 'fr_FR', choices: ['fr_FR' => 'Fran&#231;ais']);

        $this->assertSame('Français', $this->subject->label($item, 'fr_FR'));
    }

    public function testALabelCarryingAnEntityIsShownAsTheCharacterItNames(): void
    {
        $html = $this->render($this->item(
            name: 'lang',
            type: PreferenceType::SPECIAL,
            value: 'cs_CZ',
            choices: ['cs_CZ' => '&#x010c;esky'],
        ));

        $this->assertStringContainsString('>Česky<', $html);
    }

    public function testAMultiValuePreferencePostsAnArrayAndPreselectsEachStoredValue(): void
    {
        $html = $this->render($this->item(
            name: 'personalfav_playlist',
            type: PreferenceType::INTEGER,
            value: '3,7',
            choices: ['3' => 'Trois', '5' => 'Cinq', '7' => 'Sept'],
        ));

        $this->assertStringContainsString('name="personalfav_playlist[]"', $html);
        $this->assertStringContainsString('multiple="multiple"', $html);
        $this->assertStringContainsString('<option value="3" selected="selected">', $html);
        $this->assertStringContainsString('<option value="5">', $html);
        $this->assertStringContainsString('<option value="7" selected="selected">', $html);
    }

    public function testAMultiValuePreferenceReadsAsItsLabelsJoined(): void
    {
        $item = $this->item(name: 'personalfav_playlist', value: '3,7', choices: ['3' => 'Trois', '7' => 'Sept']);

        $this->assertSame('Trois, Sept', $this->subject->label($item, '3,7'));
    }

    /**
     * The placeholder must read the subject's own `transcode_bitrate`, not whatever the operator's session
     * happens to hold: an admin editing someone else's bitrate must see that account's base rate, not theirs.
     */
    public function testAnOverridableBitrateShowsTheSubjectsOwnBaseRateAsAPlaceholderRatherThanAZero(): void
    {
        $html = $this->render($this->item(name: 'transcode_bitrate_webplayer', value: '0', numberFallback: '192000'));

        $this->assertStringContainsString('value=""', $html);
        $this->assertStringContainsString('placeholder="192000"', $html);
    }

    public function testAnOverridableBitrateWithNoKnownBaseRateFallsBackToAPlainDefault(): void
    {
        $html = $this->render($this->item(name: 'transcode_bitrate_webplayer', value: '0'));

        $this->assertStringContainsString('placeholder="128000"', $html);
    }

    public function testANumericValueMatchesItsOptionWhateverItsSpelling(): void
    {
        $item = $this->item(name: 'jp_volume', type: PreferenceType::SPECIAL, value: '0.8', choices: ['0.80' => '80%']);

        $this->assertSame('80%', $this->subject->label($item, '0.8'), 'the stored 0.8 is the option 0.80');
        $this->assertSame('80%', $this->subject->label($item, '0.80'));
        $this->assertSame('0.85', $this->subject->label($item, '0.85'), 'a value with no option keeps its raw form');
    }

    public function testAnUnknownOrEmptyValueIsSpelledOutRatherThanLeftBlank(): void
    {
        $item = $this->item(name: 'transcode', type: PreferenceType::STRING, value: '', choices: ['never' => 'Never']);

        $this->assertSame(T_('(empty)'), $this->subject->label($item, ''));
        $this->assertSame(T_('(empty)'), $this->subject->label($item, null));
        $this->assertSame('wat', $this->subject->label($item, 'wat'), 'a value with no option keeps its raw form');
    }

    public function testAnUnsetSecretGetsNoPlaceholder(): void
    {
        $html = $this->render($this->item(name: 'daap_pass', type: PreferenceType::STRING, value: '', isSecret: true));

        $this->assertStringContainsString('placeholder=""', $html);
    }

    public function testARateLimitStepsByKilobytes(): void
    {
        $this->assertStringContainsString('step="1024"', $this->render($this->item(name: 'rate_limit', value: '8192')));
    }

    public function testAReadOnlyFieldShowsTheLabelOfItsStoredValue(): void
    {
        $html = $this->render($this->item(
            name: 'transcode',
            type: PreferenceType::STRING,
            value: 'always',
            choices: ['never' => 'Never', 'always' => 'Always'],
            editable: false,
        ));

        $this->assertStringContainsString('Always', $html);
    }

    public function testAReadOnlySecretIsNeverSpelledOut(): void
    {
        $html = $this->render($this->item(name: 'daap_pass', value: '', editable: false, isSecret: true, secretIsSet: true));

        $this->assertStringContainsString('******', $html);
    }

    public function testASecretIsFindableWithoutHandingItsValueAround(): void
    {
        $html = $this->render($this->item(name: 'daap_pass', type: PreferenceType::STRING, value: '', isSecret: true, secretIsSet: true));

        $this->assertStringContainsString('data-pref="daap_pass"', $html);
        $this->assertStringContainsString('data-initial=""', $html, 'anything typed counts as a change');
        $this->assertStringContainsString('data-pref-secret', $html, 'the page has to know not to echo it back');
        $this->assertStringContainsString('data-default=""', $html, 'otherwise every secret reads as modified');
        $this->assertStringNotContainsString('data-server=', $html);
    }

    public function testASecretIsNeverPrefilledAndSaysOnlyWhetherItIsSet(): void
    {
        $html = $this->render($this->item(name: 'daap_pass', type: PreferenceType::STRING, value: '', isSecret: true, secretIsSet: true));

        $this->assertStringContainsString('type="password"', $html);
        $this->assertStringContainsString('value=""', $html);
        $this->assertStringContainsString('placeholder="******"', $html);
        $this->assertStringContainsString('autocomplete="new-password"', $html);
        $this->assertStringContainsString('data-default=""', $html, 'blank is its only default');
    }

    public function testASecretNeverLeaksTheServerValueEither(): void
    {
        $html = $this->render($this->item(name: 'daap_pass', value: '', isSecret: true, secretIsSet: true));

        $this->assertStringNotContainsString('data-server=', $html);
    }

    public function testAStoredValueReadsAsTheLabelTheControlWouldShow(): void
    {
        $boolean = $this->item(name: 'download', type: PreferenceType::BOOLEAN, value: '1');
        $choice  = $this->item(name: 'transcode', type: PreferenceType::STRING, value: 'always', choices: ['never' => 'Never', 'always' => 'Always']);

        $this->assertSame($this->subject->label($boolean, '0'), $this->optionLabel($this->render($boolean), '0'));
        $this->assertSame('Always', $this->subject->label($choice, 'always'));
        $this->assertSame('Never', $this->subject->label($choice, 'never'));
    }

    public function testEveryControlCarriesWhatTheUndoButtonsNeed(): void
    {
        $html = $this->render($this->item(name: 'popular_threshold', value: '25', shippedDefault: '10'));

        $this->assertStringContainsString('data-pref="popular_threshold"', $html);
        $this->assertStringContainsString('data-default="10"', $html);
        $this->assertStringContainsString('data-initial="25"', $html);
        $this->assertStringContainsString('id="pref-popular_threshold"', $html);
    }

    /**
     * The page finds its controls by `data-pref`. A render that forgets it produces a field nobody can
     * save: that is how secrets shipped unsaveable, with the submit button staying disabled.
     */
    public function testEveryEditableControlCarriesTheAttributeThePageFindsItBy(): void
    {
        $cases = [
            'a boolean' => $this->item(name: 'download', type: PreferenceType::BOOLEAN, value: '1'),
            'a number' => $this->item(name: 'popular_threshold', value: '10'),
            'a bitrate' => $this->item(name: 'rate_limit', value: '8192'),
            'free text' => $this->item(name: 'site_title', type: PreferenceType::STRING, value: 'x'),
            'a choice list' => $this->item(name: 'transcode', type: PreferenceType::STRING, value: 'never', choices: ['never' => 'Never']),
            'a multi-select' => $this->item(name: 'personalfav_playlist', value: '3', choices: ['3' => 'Trois']),
            'a secret' => $this->item(name: 'daap_pass', type: PreferenceType::STRING, value: '', isSecret: true),
        ];

        foreach ($cases as $what => $item) {
            $html = $this->render($item);

            $this->assertStringContainsString('data-pref="' . $item->name . '"', $html, $what . ' must be findable');
            $this->assertStringContainsString('data-initial=', $html, $what . ' must say what it started as');
        }
    }

    public function testTheRestorePointsUseTheOptionSpellingAndNotTheStoredOne(): void
    {
        $item = $this->item(
            name: 'jp_volume',
            type: PreferenceType::SPECIAL,
            value: '0.8',
            choices: ['0.00' => '0%', '0.80' => '80%'],
            shippedDefault: '0.8',
        );
        $html = $this->render($item);

        $this->assertStringContainsString('data-default="0.80"', $html, 'restoring 0.8 must land on the 0.80 option');
        $this->assertStringContainsString('data-initial="0.80"', $html);
        $this->assertStringContainsString('<option value="0.80" selected="selected">', $html);
    }

    public function testTheServerValueIsAbsentWhenTheSubjectIsTheServer(): void
    {
        $this->assertStringNotContainsString('data-server=', $this->render($this->item()));
    }

    public function testValuesAreEscaped(): void
    {
        $html = $this->render($this->item(name: 'site_title', type: PreferenceType::STRING, value: '"><script>alert(1)</script>'));

        $this->assertStringNotContainsString('<script>', $html);
        $this->assertStringContainsString('&quot;&gt;&lt;script&gt;', $html);
    }

    public function testWhatAZeroMeansIsSpelledOutBesideTheFieldRatherThanInTheUnit(): void
    {
        $zero = $this->render($this->item(name: 'rate_limit', value: '0'));
        $set  = $this->render($this->item(name: 'rate_limit', value: '8192'));

        $this->assertStringNotContainsString('(0 =', $zero, 'the parenthesis is what wrapped under the field');
        $this->assertMatchesRegularExpression('/data-pref-zero(?!.*hidden)/', $zero);
        $this->assertStringContainsString('data-pref-zero hidden', $set, 'a set value has nothing to explain');
    }

    protected function setUp(): void
    {
        $this->subject = new PreferenceInputRenderer();
    }

    private function item(
        string $name = 'popular_threshold',
        PreferenceType $type = PreferenceType::INTEGER,
        string $value = '10',
        ?array $choices = null,
        bool $editable = true,
        bool $isSecret = false,
        bool $secretIsSet = false,
        ?string $shippedDefault = '10',
        ?string $numberFallback = null,
    ): PreferenceItem {
        return new PreferenceItem(
            name: $name,
            description: 'whatever',
            type: $type,
            subcategory: null,
            level: 25,
            value: $value,
            shippedDefault: $shippedDefault,
            systemValue: null,
            choices: $choices,
            editable: $editable,
            isSecret: $isSecret,
            secretIsSet: $secretIsSet,
            help: null,
            numberFallback: $numberFallback,
        );
    }

    private function optionLabel(string $html, string $value): string
    {
        preg_match('/<option value="' . $value . '"[^>]*>([^<]*)</', $html, $matches);

        return $matches[1] ?? '';
    }

    private function render(PreferenceItem $item, bool $isSelf = true): string
    {
        return $this->subject->render($item, $this->subject($isSelf));
    }

    private function renderGrantLink(string $challenge, string $apiKey = 'an-api-key'): string
    {
        // `Plugin` builds instances with make() through the `global $dic` bridge
        $dic = $this->createMock(Container::class);
        $dic->method('get')->willReturnCallback(fn(string $id): object => match ($id) {
            UpdateInfoRepositoryInterface::class => $this->createMock(UpdateInfoRepositoryInterface::class),
            default => $this->createMock(LoggerInterface::class),
        });
        $dic->method('make')->willReturnCallback($this->buildWithMockedDependencies(...));
        $GLOBALS['dic'] = $dic;

        AmpConfig::set('lastfm_api_key', $apiKey, true);
        $_SESSION['forms'] = [];

        $user        = $this->createMock(User::class);
        $user->id    = 1;
        $user->prefs = ['lastfm_challenge' => $challenge];
        $user->method('getId')->willReturn(1);

        return $this->subject->render(
            $this->item(name: 'lastfm_grant_link', type: PreferenceType::STRING, value: ''),
            PreferenceSubject::ownPreferences($user)
        );
    }

    private function subject(bool $isSelf = true): PreferenceSubject
    {
        $user           = $this->createMock(User::class);
        $user->fullname = 'u';
        $user->method('getId')->willReturn($isSelf ? 1 : 2);

        return $isSelf
            ? PreferenceSubject::ownPreferences($user)
            : PreferenceSubject::otherUser($user, $this->createConfiguredMock(User::class, ['getId' => 1]));
    }
}
