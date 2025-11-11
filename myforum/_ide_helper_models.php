<?php

// @formatter:off
// phpcs:ignoreFile
/**
 * A helper file for your Eloquent Models
 * Copy the phpDocs from this file to the correct Model,
 * And remove them from this file, to prevent double declarations.
 *
 * @author Barry vd. Heuvel <barryvdh@gmail.com>
 */


namespace App\Models{
/**
 * @property int $id
 * @property string $nama_situs
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|Daftarsitus newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Daftarsitus newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Daftarsitus query()
 * @method static \Illuminate\Database\Eloquent\Builder|Daftarsitus whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Daftarsitus whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Daftarsitus whereNamaSitus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Daftarsitus whereUpdatedAt($value)
 */
	class Daftarsitus extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string|null $slug
 * @property int $case_id
 * @property string $topik_title
 * @property string $link_gambar
 * @property string $topik_deskripsi
 * @property string $created_for
 * @property string $created_for_name
 * @property string $created_by
 * @property string $created_by_name
 * @property string|null $site_situs
 * @property string|null $periode
 * @property int $status_kesalahan 1=tidak bersalah, 2=bersalah low, 3=bersalah medium, 4=bersalah high,
 * @property int $status_case 1=open, 2=on progress, 3=pending, 4=close
 * @property int $soft_delete
 * @property string|null $recovery_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|Forumaudit newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Forumaudit newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Forumaudit query()
 * @method static \Illuminate\Database\Eloquent\Builder|Forumaudit whereCaseId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Forumaudit whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Forumaudit whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Forumaudit whereCreatedByName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Forumaudit whereCreatedFor($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Forumaudit whereCreatedForName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Forumaudit whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Forumaudit whereLinkGambar($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Forumaudit wherePeriode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Forumaudit whereRecoveryBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Forumaudit whereSiteSitus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Forumaudit whereSlug($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Forumaudit whereSoftDelete($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Forumaudit whereStatusCase($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Forumaudit whereStatusKesalahan($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Forumaudit whereTopikDeskripsi($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Forumaudit whereTopikTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Forumaudit whereUpdatedAt($value)
 */
	class Forumaudit extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string|null $slug
 * @property int $parent_forum_id
 * @property int $parent_case_id
 * @property string $deskripsi_post
 * @property string $updated_by
 * @property string $updated_by_name
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|Forumauditpost newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Forumauditpost newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Forumauditpost query()
 * @method static \Illuminate\Database\Eloquent\Builder|Forumauditpost whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Forumauditpost whereDeskripsiPost($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Forumauditpost whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Forumauditpost whereParentCaseId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Forumauditpost whereParentForumId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Forumauditpost whereSlug($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Forumauditpost whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Forumauditpost whereUpdatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Forumauditpost whereUpdatedByName($value)
 */
	class Forumauditpost extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $nama_jabatan
 * @property int $bagian_posisi
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|Jabatan newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Jabatan newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Jabatan query()
 * @method static \Illuminate\Database\Eloquent\Builder|Jabatan whereBagianPosisi($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Jabatan whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Jabatan whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Jabatan whereNamaJabatan($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Jabatan whereUpdatedAt($value)
 */
	class Jabatan extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int|null $user_parent_id
 * @property string $id_admin
 * @property int|null $posisi_kerja
 * @property string $nama_posisi
 * @property int|null $id_jabatan
 * @property string $nama_jabatan
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|Logposisijabatan newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Logposisijabatan newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Logposisijabatan query()
 * @method static \Illuminate\Database\Eloquent\Builder|Logposisijabatan whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Logposisijabatan whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Logposisijabatan whereIdAdmin($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Logposisijabatan whereIdJabatan($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Logposisijabatan whereNamaJabatan($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Logposisijabatan whereNamaPosisi($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Logposisijabatan wherePosisiKerja($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Logposisijabatan whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Logposisijabatan whereUserParentId($value)
 */
	class Logposisijabatan extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string|null $menu_id
 * @property string|null $menu_deskripsi
 * @property string|null $menu_link
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|Menu newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Menu newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Menu query()
 * @method static \Illuminate\Database\Eloquent\Builder|Menu whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Menu whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Menu whereMenuDeskripsi($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Menu whereMenuId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Menu whereMenuLink($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Menu whereUpdatedAt($value)
 */
	class Menu extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $nama_posisi
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|Posisi newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Posisi newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Posisi query()
 * @method static \Illuminate\Database\Eloquent\Builder|Posisi whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Posisi whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Posisi whereNamaPosisi($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Posisi whereUpdatedAt($value)
 */
	class Posisi extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string|null $id_admin
 * @property string|null $menu_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|Privilegeaccess newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Privilegeaccess newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Privilegeaccess query()
 * @method static \Illuminate\Database\Eloquent\Builder|Privilegeaccess whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Privilegeaccess whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Privilegeaccess whereIdAdmin($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Privilegeaccess whereMenuId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Privilegeaccess whereUpdatedAt($value)
 */
	class Privilegeaccess extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $id_staff
 * @property string $nama_staff
 * @property string $periode
 * @property string $site_situs
 * @property int $tidak_bersalah
 * @property int $bersalah_low
 * @property int $bersalah_medium
 * @property int $bersalah_high
 * @property int|null $total_case
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|Reporting newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Reporting newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Reporting query()
 * @method static \Illuminate\Database\Eloquent\Builder|Reporting whereBersalahHigh($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Reporting whereBersalahLow($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Reporting whereBersalahMedium($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Reporting whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Reporting whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Reporting whereIdStaff($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Reporting whereNamaStaff($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Reporting wherePeriode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Reporting whereSiteSitus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Reporting whereTidakBersalah($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Reporting whereTotalCase($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Reporting whereUpdatedAt($value)
 */
	class Reporting extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string|null $pengumuman
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|Setting newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Setting newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Setting query()
 * @method static \Illuminate\Database\Eloquent\Builder|Setting whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Setting whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Setting wherePengumuman($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Setting whereUpdatedAt($value)
 */
	class Setting extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $bulan_dari
 * @property string $bulan_ke
 * @property int $tahun
 * @property int $periode
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|SettingPeriode newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|SettingPeriode newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|SettingPeriode query()
 * @method static \Illuminate\Database\Eloquent\Builder|SettingPeriode whereBulanDari($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SettingPeriode whereBulanKe($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SettingPeriode whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SettingPeriode whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SettingPeriode wherePeriode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SettingPeriode whereTahun($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SettingPeriode whereUpdatedAt($value)
 */
	class SettingPeriode extends \Eloquent {}
}

namespace App\Models{
/**
 * @property string $topik_title
 * @property bool   $status
 * @property bool   $soft_delete
 * @property-read string $status_label
 * @property int $id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|Topik active()
 * @method static \Illuminate\Database\Eloquent\Builder|Topik inactive()
 * @method static \Illuminate\Database\Eloquent\Builder|Topik newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Topik newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Topik onlyDeleted()
 * @method static \Illuminate\Database\Eloquent\Builder|Topik query()
 * @method static \Illuminate\Database\Eloquent\Builder|Topik search(?string $s)
 * @method static \Illuminate\Database\Eloquent\Builder|Topik whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Topik whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Topik whereSoftDelete($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Topik whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Topik whereTopikTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Topik whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Topik withDeleted()
 */
	class Topik extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $id_admin
 * @property mixed $password
 * @property string $id_situs
 * @property string|null $nama_staff
 * @property string $email
 * @property string|null $nomor_paspor
 * @property string|null $masa_aktif_paspor
 * @property string|null $tanggal_join
 * @property string|null $nomor_visa
 * @property string|null $masa_aktif_visa
 * @property int|null $posisi_kerja
 * @property int|null $id_jabatan
 * @property int|null $status
 * @property string|null $remember_token
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Notifications\DatabaseNotificationCollection<int, \Illuminate\Notifications\DatabaseNotification> $notifications
 * @property-read int|null $notifications_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Laravel\Sanctum\PersonalAccessToken> $tokens
 * @property-read int|null $tokens_count
 * @method static \Database\Factories\UserFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder|User newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|User newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|User query()
 * @method static \Illuminate\Database\Eloquent\Builder|User whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereIdAdmin($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereIdJabatan($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereIdSitus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereMasaAktifPaspor($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereMasaAktifVisa($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereNamaStaff($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereNomorPaspor($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereNomorVisa($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User wherePassword($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User wherePosisiKerja($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereRememberToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereTanggalJoin($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereUpdatedAt($value)
 */
	class User extends \Eloquent {}
}

