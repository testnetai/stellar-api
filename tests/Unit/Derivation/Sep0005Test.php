<?php


namespace ZuluCrypto\StellarSdk\Test\Unit\Slip0010;


use PHPUnit\Framework\TestCase;
use ZuluCrypto\StellarSdk\Derivation\Bip39\Bip39;
use ZuluCrypto\StellarSdk\Derivation\HdNode;
use ZuluCrypto\StellarSdk\Keypair;

/**
 * Tests compatibility with SEP-0005
 *
 * See: https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0005.md
 */
class Sep0005Test extends TestCase
{
    /**
     * @dataProvider newKeypairFromMnemonicProvider
     */
    public function testNewKeypairFromMnemonic($mnemonic, $passphrase, $publicKey, $privateKey)
    {
        $keypair = Keypair::newFromMnemonic($mnemonic, $passphrase);

        $this->assertEquals($publicKey, $keypair->getPublicKey());
        $this->assertEquals($privateKey, $keypair->getSecret());
    }
    public function newKeypairFromMnemonicProvider()
    {
        return [
            [
                'cable spray genius state float twenty onion head street palace net private method loan turn phrase state blanket interest dry amazing dress blast tube',
                'p4ssphr4se',
                'GDAHPZ2NSYIIHZXM56Y36SBVTV5QKFIZGYMMBHOU53ETUSWTP62B63EQ',
                'SAFWTGXVS7ELMNCXELFWCFZOPMHUZ5LXNBGUVRCY3FHLFPXK4QPXYP2X',
            ],
        ];
    }

    /**
     * @dataProvider sep0005Provider
     */
    public function testSep0005($mnemonic, $passphrase, $masterSeedHex, $addressNodeKeyHex, $keys)
    {
        print "\n";

        $bip39 = new Bip39();
        $seedBytes = $bip39->mnemonicToSeedBytesWithErrorChecking($mnemonic, $passphrase);
        $this->assertEquals(bin2hex($seedBytes), $masterSeedHex);

        $masterNode = HdNode::newMasterNode($seedBytes);
        $addressNode = $masterNode->derivePath("m/44'/148'");
        $this->assertEquals($addressNodeKeyHex, bin2hex($addressNode->getPrivateKeyBytes()));

        $keyIdx = 0;
        foreach ($keys as $publicString => $privateString) {
            $accountNode = $addressNode->derive($keyIdx);
            $keypair = Keypair::newFromRawSeed($accountNode->getPrivateKeyBytes());

            $this->assertEquals($publicString, $keypair->getPublicKey());
            $this->assertEquals($privateString, $keypair->getSecret());

            $keyIdx++;
        }
    }
    public function sep0005Provider()
    {
        return [
            [
                'illness spike retreat truth genius clock brain pass fit cave bargain toe',
                '',
                'e4a5a632e70943ae7f07659df1332160937fad82587216a4c64315a0fb39497ee4a01f76ddab4cba68147977f3a147b6ad584c41808e8238a07f6cc4b582f186',
                'e0eec84fe165cd427cb7bc9b6cfdef0555aa1cb6f9043ff1fe986c3c8ddd22e3',
                [
                    'GDRXE2BQUC3AZNPVFSCEZ76NJ3WWL25FYFK6RGZGIEKWE4SOOHSUJUJ6' => 'SBGWSG6BTNCKCOB3DIFBGCVMUPQFYPA2G4O34RMTB343OYPXU5DJDVMN',
                    'GBAW5XGWORWVFE2XTJYDTLDHXTY2Q2MO73HYCGB3XMFMQ562Q2W2GJQX' => 'SCEPFFWGAG5P2VX5DHIYK3XEMZYLTYWIPWYEKXFHSK25RVMIUNJ7CTIS',
                    'GAY5PRAHJ2HIYBYCLZXTHID6SPVELOOYH2LBPH3LD4RUMXUW3DOYTLXW' => 'SDAILLEZCSA67DUEP3XUPZJ7NYG7KGVRM46XA7K5QWWUIGADUZCZWTJP',
                    'GAOD5NRAEORFE34G5D4EOSKIJB6V4Z2FGPBCJNQI6MNICVITE6CSYIAE' => 'SBMWLNV75BPI2VB4G27RWOMABVRTSSF7352CCYGVELZDSHCXWCYFKXIX',
                    'GBCUXLFLSL2JE3NWLHAWXQZN6SQC6577YMAU3M3BEMWKYPFWXBSRCWV4' => 'SCPCY3CEHMOP2TADSV2ERNNZBNHBGP4V32VGOORIEV6QJLXD5NMCJUXI',
                    'GBRQY5JFN5UBG5PGOSUOL4M6D7VRMAYU6WW2ZWXBMCKB7GPT3YCBU2XZ' => 'SCK27SFHI3WUDOEMJREV7ZJQG34SCBR6YWCE6OLEXUS2VVYTSNGCRS6X',
                    'GBY27SJVFEWR3DUACNBSMJB6T4ZPR4C7ZXSTHT6GMZUDL23LAM5S2PQX' => 'SDJ4WDPOQAJYR3YIAJOJP3E6E4BMRB7VZ4QAEGCP7EYVDW6NQD3LRJMZ',
                    'GAY7T23Z34DWLSTEAUKVBPHHBUE4E3EMZBAQSLV6ZHS764U3TKUSNJOF' => 'SA3HXJUCE2N27TBIZ5JRBLEBF3TLPQEBINP47E6BTMIWW2RJ5UKR2B3L',
                    'GDJTCF62UUYSAFAVIXHPRBR4AUZV6NYJR75INVDXLLRZLZQ62S44443R' => 'SCD5OSHUUC75MSJG44BAT3HFZL2HZMMQ5M4GPDL7KA6HJHV3FLMUJAME',
                    'GBTVYYDIYWGUQUTKX6ZMLGSZGMTESJYJKJWAATGZGITA25ZB6T5REF44' => 'SCJGVMJ66WAUHQHNLMWDFGY2E72QKSI3XGSBYV6BANDFUFE7VY4XNXXR',
                ]
            ],
            [
                'resource asthma orphan phone ice canvas fire useful arch jewel impose vague theory cushion top',
                '',
                '7b36d4e725b48695c3ffd2b4b317d5552cb157c1a26c46d36a05317f0d3053eb8b3b6496ba39ebd9312d10e3f9937b47a6790541e7c577da027a564862e92811',
                '2e5d4e6b54a4b96c5e887c9ec92f619a3c134d8b1059dcef15c1a9b228ae3751',
                [
                    'GAVXVW5MCK7Q66RIBWZZKZEDQTRXWCZUP4DIIFXCCENGW2P6W4OA34RH' => 'SAKS7I2PNDBE5SJSUSU2XLJ7K5XJ3V3K4UDFAHMSBQYPOKE247VHAGDB',
                    'GDFCYVCICATX5YPJUDS22KM2GW5QU2KKSPPPT2IC5AQIU6TP3BZSLR5K' => 'SAZ2H5GLAVWCUWNPQMB6I3OHRI63T2ACUUAWSH7NAGYYPXGIOPLPW3Q4',
                    'GAUA3XK3SGEQFNCBM423WIM5WCZ4CR4ZDPDFCYSFLCTODGGGJMPOHAAE' => 'SDVSSLPL76I33DKAI4LFTOAKCHJNCXUERGPCMVFT655Z4GRLWM6ZZTSC',
                    'GAH3S77QXTAPZ77REY6LGFIJ2XWVXFOKXHCFLA6HQTL3POLVZJDHHUDM' => 'SCH56YSGOBYVBC6DO3ZI2PY62GBVXT4SEJSXJOBQYGC2GCEZSB5PEVBZ',
                    'GCSCZVGV2Y3EQ2RATJ7TE6PVWTW5OH5SMG754AF6W6YM3KJF7RMNPB4Y' => 'SBWBM73VUNBGBMFD4E2BA7Q756AKVEAAVTQH34RYEUFD6X64VYL5KXQ2',
                    'GDKWYAJE3W6PWCXDZNMFNFQSPTF6BUDANE6OVRYMJKBYNGL62VKKCNCC' => 'SAVS4CDQZI6PSA5DPCC42S5WLKYIPKXPCJSFYY4N3VDK25T2XX2BTGVX',
                    'GCDTVB4XDLNX22HI5GUWHBXJFBCPB6JNU6ZON7E57FA3LFURS74CWDJH' => 'SDFC7WZT3GDQVQUQMXN7TC7UWDW5E3GSMFPHUT2TSTQ7RKWTRA4PLBAL',
                    'GBTDPL5S4IOUQHDLCZ7I2UXJ2TEHO6DYIQ3F2P5OOP3IS7JSJI4UMHQJ' => 'SA6UO2FIYC6AS2MSDECLR6F7NKCJTG67F7R4LV2GYB4HCZYXJZRLPOBB',
                    'GD3KWA24OIM7V3MZKDAVSLN3NBHGKVURNJ72ZCTAJSDTF7RIGFXPW5FQ' => 'SBDNHDDICLLMBIDZ2IF2D3LH44OVUGGAVHQVQ6BZQI5IQO6AB6KNJCOV',
                    'GB3C6RRQB3V7EPDXEDJCMTS45LVDLSZQ46PTIGKZUY37DXXEOAKJIWSV' => 'SDHRG2J34MGDAYHMOVKVJC6LX2QZMCTIKRO5I4JQ6BJQ36KVL6QUTT72',
                ]
            ],
            [
                'bench hurt jump file august wise shallow faculty impulse spring exact slush thunder author capable act festival slice deposit sauce coconut afford frown better',
                '',
                '937ae91f6ab6f12461d9936dfc1375ea5312d097f3f1eb6fed6a82fbe38c85824da8704389831482db0433e5f6c6c9700ff1946aa75ad8cc2654d6e40f567866',
                'df474e0dc2711089b89af6b089aceeb77e73120e9f895bd330a36fa952835ea8',
                [
                    'GC3MMSXBWHL6CPOAVERSJITX7BH76YU252WGLUOM5CJX3E7UCYZBTPJQ' => 'SAEWIVK3VLNEJ3WEJRZXQGDAS5NVG2BYSYDFRSH4GKVTS5RXNVED5AX7',
                    'GB3MTYFXPBZBUINVG72XR7AQ6P2I32CYSXWNRKJ2PV5H5C7EAM5YYISO' => 'SBKSABCPDWXDFSZISAVJ5XKVIEWV4M5O3KBRRLSPY3COQI7ZP423FYB4',
                    'GDYF7GIHS2TRGJ5WW4MZ4ELIUIBINRNYPPAWVQBPLAZXC2JRDI4DGAKU' => 'SD5CCQAFRIPB3BWBHQYQ5SC66IB2AVMFNWWPBYGSUXVRZNCIRJ7IHESQ',
                    'GAFLH7DGM3VXFVUID7JUKSGOYG52ZRAQPZHQASVCEQERYC5I4PPJUWBD' => 'SBSGSAIKEF7JYQWQSGXKB4SRHNSKDXTEI33WZDRR6UHYQCQ5I6ZGZQPK',
                    'GAXG3LWEXWCAWUABRO6SMAEUKJXLB5BBX6J2KMHFRIWKAMDJKCFGS3NN' => 'SBIZH53PIRFTPI73JG7QYA3YAINOAT2XMNAUARB3QOWWVZVBAROHGXWM',
                    'GA6RUD4DZ2NEMAQY4VZJ4C6K6VSEYEJITNSLUQKLCFHJ2JOGC5UCGCFQ' => 'SCVM6ZNVRUOP4NMCMMKLTVBEMAF2THIOMHPYSSMPCD2ZU7VDPARQQ6OY',
                    'GCUDW6ZF5SCGCMS3QUTELZ6LSAH6IVVXNRPRLAUNJ2XYLCA7KH7ZCVQS' => 'SBSHUZQNC45IAIRSAHMWJEJ35RY7YNW6SMOEBZHTMMG64NKV7Y52ZEO2',
                    'GBJ646Q524WGBN5X5NOAPIF5VQCR2WZCN6QZIDOSY6VA2PMHJ2X636G4' => 'SC2QO2K2B4EBNBJMBZIKOYSHEX4EZAZNIF4UNLH63AQYV6BE7SMYWC6E',
                    'GDHX4LU6YBSXGYTR7SX2P4ZYZSN24VXNJBVAFOB2GEBKNN3I54IYSRM4' => 'SCGMC5AHAAVB3D4JXQPCORWW37T44XJZUNPEMLRW6DCOEARY3H5MAQST',
                    'GDXOY6HXPIDT2QD352CH7VWX257PHVFR72COWQ74QE3TEV4PK2KCKZX7' => 'SCPA5OX4EYINOPAUEQCPY6TJMYICUS5M7TVXYKWXR3G5ZRAJXY3C37GF',
                ]
            ],
            [
                'cable spray genius state float twenty onion head street palace net private method loan turn phrase state blanket interest dry amazing dress blast tube',
                'p4ssphr4se',
                'd425d39998fb42ce4cf31425f0eaec2f0a68f47655ea030d6d26e70200d8ff8bd4326b4bdf562ea8640a1501ae93ccd0fd7992116da5dfa24900e570a742a489',
                'c83c61dc97d37832f0f20e258c3ba4040a258800fd14abaff124a4dee114b17e',
                [
                    'GDAHPZ2NSYIIHZXM56Y36SBVTV5QKFIZGYMMBHOU53ETUSWTP62B63EQ' => 'SAFWTGXVS7ELMNCXELFWCFZOPMHUZ5LXNBGUVRCY3FHLFPXK4QPXYP2X',
                    'GDY47CJARRHHL66JH3RJURDYXAMIQ5DMXZLP3TDAUJ6IN2GUOFX4OJOC' => 'SBQPDFUGLMWJYEYXFRM5TQX3AX2BR47WKI4FDS7EJQUSEUUVY72MZPJF',
                    'GCLAQF5H5LGJ2A6ACOMNEHSWYDJ3VKVBUBHDWFGRBEPAVZ56L4D7JJID' => 'SAF2LXRW6FOSVQNC4HHIIDURZL4SCGCG7UEGG23ZQG6Q2DKIGMPZV6BZ',
                    'GBC36J4KG7ZSIQ5UOSJFQNUP4IBRN6LVUFAHQWT2ODEQ7Y3ASWC5ZN3B' => 'SDCCVBIYZDMXOR4VPC3IYMIPODNEDZCS44LDN7B5ZWECIE57N3BTV4GQ',
                    'GA6NHA4KPH5LFYD6LZH35SIX3DU5CWU3GX6GCKPJPPTQCCQPP627E3CB' => 'SA5TRXTO7BG2Z6QTQT3O2LC7A7DLZZ2RBTGUNCTG346PLVSSHXPNDVNT',
                    'GBOWMXTLABFNEWO34UJNSJJNVEF6ESLCNNS36S5SX46UZT2MNYJOLA5L' => 'SDEOED2KPHV355YNOLLDLVQB7HDPQVIGKXCAJMA3HTM4325ZHFZSKKUC',
                    'GBL3F5JUZN3SQKZ7SL4XSXEJI2SNSVGO6WZWNJLG666WOJHNDDLEXTSZ' => 'SDYNO6TLFNV3IM6THLNGUG5FII4ET2H7NH3KCT6OAHIUSHKR4XBEEI6A',
                    'GA5XPPWXL22HFFL5K5CE37CEPUHXYGSP3NNWGM6IK6K4C3EFHZFKSAND' => 'SDXMJXAY45W3WEFWMYEPLPIF4CXAD5ECQ37XKMGY5EKLM472SSRJXCYD',
                    'GDS5I7L7LWFUVSYVAOHXJET2565MGGHJ4VHGVJXIKVKNO5D4JWXIZ3XU' => 'SAIZA26BUP55TDCJ4U7I2MSQEAJDPDSZSBKBPWQTD5OQZQSJAGNN2IQB',
                    'GBOSMFQYKWFDHJWCMCZSMGUMWCZOM4KFMXXS64INDHVCJ2A2JAABCYRR' => 'SDXDYPDNRMGOF25AWYYKPHFAD3M54IT7LCLG7RWTGR3TS32A4HTUXNOS',
                ]
            ],
        ];
    }
}