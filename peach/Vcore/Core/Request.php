<?php

namespace Core;
use Illuminate\Support\Arr;
use \Symfony\Component\HttpFoundation\Request as SymfonyRequest;

/**
 * ��չ������� ���ڼ�����Щ����ķ���
 *  ��:
 *      ��ҳpagination
 *
 * Class Request
 * @package Core
 */
class Request extends SymfonyRequest
{
    /**
     * ��ȡ��Ӧ�� ��������������
     *
     * @return \Symfony\Component\HttpFoundation\ParameterBag
     */
    protected function getInputSource()
    {
        return $this->getMethod() == 'GET' ? $this->query : $this->request;
    }

    /**
     * ��ȡ�ύ������ֵ�е�����
     *
     * @param null $key Ҫ��ȡ��Ӧ���ύ��ֵ
     * @param null $default Ĭ��ֵ ��ȡ�����������Ĭ�ϵ�
     * @return mixed
     */
    public function input($key=null,$default=null)
    {
        $input = $this->getInputSource()->all() + $this->query->all();
        return Arr::get($input,$key,$default);
    }

    /**
     * ��չ��SymfonyRequest �ķ��� ���ڻ�ȡurl��ַ
     *
     * @return string
     */
    public function url()
    {
        return rtrim(preg_replace('/\?.*/','',$this->getUri()),'/');
    }

}