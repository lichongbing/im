<?php
use Overtrue\Pinyin\Pinyin;

/**
 * Class HanziToPinyin
 */
class HanziToPinyin
{

    /**
     * @var null|Pinyin
     */
    protected $pinyin = null;

    /**
     * HanziToPinyin constructor.
     */
    public function __construct()
    {
        $this->pinyin = new Pinyin;
    }

    /**
     * 二维数组根据首字母分组排序
     * @param  array  $data      二维数组
     * @param  string $targetKey 首字母的键名
     * @return array             根据首字母关联的二维数组
     */
    public function groupByInitials(array $data, $targetKey = 'name')
    {
        $data = array_map(function ($item) use ($targetKey) {
            return array_merge($item, [
                'initials' => $this->getInitials($item[$targetKey]),
            ]);
        }, $data);
        $data = $this->sortInitials($data);
        return $data;
    }

    /**
     * 按字母排序
     * @param  array  $data
     * @return array
     */
    public function sortInitials(array $data)
    {
        $sortData = [];
        foreach ($data as $key => $value) {
            $sortData[$value['initials']][$key] = $value;
        }
        ksort($sortData);
        return $sortData;
    }

    /**
     * 获取首字母
     * @param  string $str 汉字字符串
     * @return string 首字母
     */
    public function getInitials($str)
    {
        if (empty($str)) {
            return '';
        }
        return strtoupper(substr($this->pinyin->abbr($str, PINYIN_KEEP_ENGLISH), 0, 1));
    }

}
