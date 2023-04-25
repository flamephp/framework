<?php

declare(strict_types=1);

namespace Flame\Sms;

use Exception;
use Overtrue\EasySms\EasySms;

class Sms
{
    /**
     * 短信配置
     */
    private array $config;

    public function __construct()
    {
        $this->config = config('sms');
    }

    /**
     * @throws Exception
     */
    public function send(string $mobile, string $template, array $data): array
    {
        $easySms = new EasySms($this->config);

        return $easySms->send($mobile, [
            'content' => $this->contentParser($template, $data),
            'template' => $template,
            'data' => $data,
        ]);

    }

    /**
     * 短信内容模板解析
     *
     * @throws Exception
     */
    private function contentParser(string $template, array $data): string
    {
        $templates = $this->config['templates'];
        if (isset($templates[$template])) {
            // 替换消息变量
            preg_match_all('/\$\{(.+?)\\\}/', $templates[$template], $matches);
            foreach ($matches[1] as $vo) {
                $templates[$template] = str_replace('${'.$vo.'}', $data[$vo], $templates[$template]);
            }

            return $templates[$template];
        }

        throw new Exception('短信模板没有找到');
    }
}
