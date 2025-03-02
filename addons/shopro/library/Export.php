<?php

namespace addons\shopro\library;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use Cache\Adapter\Redis\RedisCachePool;
use Cache\Bridge\SimpleCache\SimpleCacheBridge;
use PhpOffice\PhpSpreadsheet\Settings;
use addons\shopro\library\Redis;

class Export
{
    protected $last_memory_limit = '256M';

    protected $config = [
        'save_type' => 'download',          // 保存方式，download:直接下载， save:保存到服务器

        'list_rows' => 1000,                // 每次查询数据条数

        'save_path' => RUNTIME_PATH . 'storage/export/',        // 如果保存到服务器，保存路径

        'memory_limit' => '512M',       // php 进程内存限制(仅在导出过程生效), 注意单位 M

        'time_limit' => 0,              // php 超时时间，单位秒，0 不限制

        'cache_driver' => 'default',        // 数据临时存储驱动，default:内存, redis:redis

        'redis_select' => null,         // 导出大数据时，请尽量配置一个空的 redis db 库(导出失败，可以随时清除)，以免导出失败，db 库中被存入大量垃圾数据
    ];


    public function __construct()
    {
        $this->config['time_limit'] = intval($this->config['time_limit']);
        $this->config['list_rows'] = intval($this->config['list_rows']) ? intval($this->config['list_rows']) : 1000;

        // 设置导出限制
        $this->setLimit();

        // 设置导出时缓存
        $this->setCache();
    }


    /**
     * 导出
     *
     * @param array $params
     * @param \Closure $callback
     * @return void
     */
    public function export($params, \Closure $callback)
    {
        $fileName = $params['file_name'];       // 文件名
        $cellTitles = $params['cell_titles'];   // 标题
        $cell_num = count($cellTitles);         // 标题数量
        $total = $params['total'];              // 记录总条数
        $is_sub_cell = $params['is_sub_cell'] ?? false;              // 是否有子数据
        $sub_start_cell = $params['sub_start_cell'] ?? null;              // 子数据开始字段
        $sub_field = $params['sub_field'] ?? null;              // 子数据字段名

        $cellName = array('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z', 'AA', 'AB', 'AC', 'AD', 'AE', 'AF', 'AG', 'AH', 'AI', 'AJ', 'AK', 'AL', 'AM', 'AN', 'AO', 'AP', 'AQ', 'AR', 'AS', 'AT', 'AU', 'AV', 'AW', 'AX', 'AY', 'AZ');
        $last_cell_key = $cellName[$cell_num - 1];         // 最后一列的标头
        // 最后一页
        $last_page = intval(ceil($total / $this->config['list_rows']));

        // 实例化excel
        $spreadsheet = new Spreadsheet();

        // 初始化工作簿
        $sheet = $spreadsheet->getActiveSheet(0);

        // 给表头字体加粗
        $sheet->getStyle('A1:' . $last_cell_key . '1')->getFont()->setBold(true);

        // 表头
        $i = 0;
        foreach ($cellTitles as $key => $cell) {
            $sheet->setCellValue($cellName[$i] . '1', $cell);
            $i++;
        }

        $cell_total = 2;        // 当前表格已有行数
        for($page = 1;$page <= $last_page;$page++) {
            $is_last_page = $page == $last_page ? true : false;

            // 获取数据
            $datas = $callback([
                'page' => $page,
                'list_rows' => $this->config['list_rows'],
                'is_last_page' => $is_last_page
            ]);

            foreach ($datas as $key => $data) {
                if ($is_last_page && $key == count($datas) - 1 && (!is_array($data) || count($data) == 1)) {
                    $total_text = is_array($data) ? current($data) : $data;
                    $sheet->mergeCells('A' . $cell_total . ':' . $last_cell_key . $cell_total);
                    $sheet->setCellValue('A' . $cell_total, $total_text);
                } else {
                    $items_count = 1;
                    if ($is_sub_cell) {
                        $items_count = count($data[$sub_field]);
                    }
                    $items_count = $items_count >= 1 ? $items_count : 1;
                    // 每条记录设置边框
                    // $sheet->getStyle('A' . ($cell_total).':' . $last_cell_key . ($cell_total + $items_count - 1))->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

                    $i = 0;     // 当前循环到第几列了
                    $sub_start = false;
                    foreach ($cellTitles as $k => $cell) {
                        if ($k == $sub_start_cell) {
                            // 如果有子数据，是否循环到了子数据
                            $sub_start = true;
                        }

                        if ($is_sub_cell) {
                            if (!$sub_start) {
                                // 循环主数据
                                $current_text = $data[$k] ?? '';
                                if ($items_count > 1) {
                                    // items 有多个，需要合并单元格
                                    $sheet->mergeCells($cellName[$i] . ($cell_total) . ':' . $cellName[$i] . ($cell_total + $items_count - 1));
                                    $sheet->getCell($cellName[$i] . ($cell_total))->getStyle()->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
                                }
                                $sheet->setCellValue($cellName[$i] . ($cell_total), $current_text);
                            } else {
                                // 循环子数据
                                foreach ($data[$sub_field] as $j => $sub) {
                                    $current_text = $sub[$k] ?? '';
                                    $sheet->setCellValue($cellName[$i] . ($cell_total + $j), $current_text);
                                }
                            }
                        } else {
                            $current_text = $data[$k] ?? '';
                            $sheet->setCellValue($cellName[$i] . $cell_total, $current_text);
                        }

                        $i++;
                    }

                    // 增加数据写入条数
                    $cell_total = $cell_total + $items_count;
                }
            }

        }
        // 设置表格边框
        $sheet->getStyle('A1:' . $last_cell_key . $cell_total)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

        // ini_set('memory_limit', '256M');
        return $this->output($spreadsheet, $fileName);
    }



    /**
     * 输出
     *
     * @param object $spreadsheet
     * @param string $fileName
     * @return void
     */
    public function output($spreadsheet, $fileName)
    {
        $class_name = '\\PhpOffice\\PhpSpreadsheet\\Writer\\Xlsx';
        if (!class_exists($class_name)) {
            error_stop('文件输出格式不支持');
        }

        if ($this->config['save_type'] == 'save') {
            // 初始化目录
            if (!is_dir($this->config['save_path'])) {
                @mkdir($this->config['save_path'], 0755, true);
            }
            
            $save_file = $this->config['save_path'] . $fileName . '-' . date('YmdHis') .  '.xlsx';

            $result = [
                'file_path' => str_replace(ROOT_PATH, '项目目录/', $save_file)
            ];
        } else {
            $save_file = $fileName . '-' . date('YmdHis') .  '.xlsx';

            $result = [
                'file_name' => $save_file
            ];

            ob_end_clean();
            header('pragma:public');
            header("Content-type:application/octet-stream; charset=utf-8; name=" . urlencode($save_file));
            header("Content-Disposition:attachment; filename=" . urlencode($save_file));        //attachment新窗口打印inline本窗口打印

            $save_file = 'php://output';
        }
        
        $writer = new $class_name($spreadsheet);
        $writer->save($save_file);

        return $result;

        // 修改为原始内存限制，会影响文件下载，暂时不修改回原来内存
        // ini_set('memory_limit', $this->last_memory_limit);
    }



    /**
     * 设置php 进程内存限制
     *
     * @return void
     */
    public function setLimit()
    {
        // 不限时
        set_time_limit($this->config['time_limit']);
        // 根据需要调大内存限制
        $this->last_memory_limit = ini_get('memory_limit');

        ini_set('memory_limit', $this->config['memory_limit']);
    }


    /**
     * 设置导出临时缓存
     *
     * @return void
     */
    public function setCache()
    {
        // 设置缓存
        if ($this->config['cache_driver'] == 'redis') {
            // 将表格数据暂存 redis,可以降低 php 进程内存占用
            if (!class_exists(RedisCachePool::class)) {
                // 需要安装扩展包  composer require cache/simple-cache-bridge cache/redis-adapter
                error_stop('请安装扩展包：composer require cache/simple-cache-bridge cache/redis-adapter');
            }
            if (is_null($this->config['redis_select'])) {
                error_stop('请在 addons/shopro/library/Export.php 文件，defaultConfig 中配置 redis_select 库');
            }

            $options = [
                'select' => $this->config['redis_select']
            ];
            $redis = (new Redis($options))->getRedis();         // 不冲突
            $pool = new RedisCachePool($redis);
            $simpleCache = new SimpleCacheBridge($pool);

            Settings::setCache($simpleCache);
        }
    }


    public function getConfig() 
    {
        return $this->config;
    }
}
