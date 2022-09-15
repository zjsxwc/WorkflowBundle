<?php


namespace WorkflowBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * NodeAssignment
 *
 * @ORM\Table(name="template_workflow")
 * @ORM\Entity(repositoryClass="WorkflowBundle\Repository\TemplateWorkflowRepository")
 */
class TemplateWorkflow extends AbstractWorkflow
{
    /**
     * @var string
     *
     * @ORM\Column(name="a_text", type="string", length=255, nullable=true)
     */
    public $aText;
    /**
     * @var string
     *
     * @ORM\Column(name="b_text", type="string", length=255, nullable=true)
     */
    public $bText;
    /**
     * @var string
     *
     * @ORM\Column(name="c_text", type="string", length=255, nullable=true)
     */
    public $cText;
    /**
     * @var string
     *
     * @ORM\Column(name="d_text", type="string", length=255, nullable=true)
     */
    public $dText;
    /**
     * @var string
     *
     * @ORM\Column(name="e_text", type="string", length=255, nullable=true)
     */
    public $eText;
    /**
     * @var string
     *
     * @ORM\Column(name="f_text", type="string", length=255, nullable=true)
     */
    public $fText;
    /**
     * @var string
     *
     * @ORM\Column(name="g_text", type="string", length=255, nullable=true)
     */
    public $gText;
    /**
     * @var string
     *
     * @ORM\Column(name="h_text", type="string", length=255, nullable=true)
     */
    public $hText;
    /**
     * @var string
     *
     * @ORM\Column(name="i_text", type="string", length=255, nullable=true)
     */
    public $iText;
    /**
     * @var string
     *
     * @ORM\Column(name="j_text", type="string", length=255, nullable=true)
     */
    public $jText;
    /**
     * @var string
     *
     * @ORM\Column(name="k_text", type="string", length=255, nullable=true)
     */
    public $kText;
    /**
     * @var string
     *
     * @ORM\Column(name="x_text", type="string", length=255, nullable=true)
     */
    public $xText;
    /**
     * @var string
     *
     * @ORM\Column(name="y_text", type="text", nullable=true)
     */
    public $yText;
    /**
     * @var string
     *
     * @ORM\Column(name="z_text", type="string", length=255, nullable=true)
     */
    public $zText;
    /**
     * @var string
     *
     * @ORM\Column(name="w_text", type="string", length=255, nullable=true)
     */
    public $wText;


    public function nodeTransitions()
    {
        return [
            "nodeList" => ["A", "B", "C", "D", "E", "F", "G", "H", "I","J","K","X", "Y","Z","W"],//这里A、B等可以用多个字中文表示
            "transitions" => [
                "A" => "B",
                "B" => ["C", "D"],//B 同时分叉并行 C 与 D， C与D是临时状态, 所有分支临时节点都不能处理时指派下一节点处理人，得由分叉上一级节点比如这里的B来指派C与D分叉提交后的处理人,
                //分叉节点的下一个节点不允许继续分叉 必须聚合到一个节点处理，
                //后期 分叉节点 可以 关联发起一个 workflow（由workflow记录这个分叉nodeAssignment的id，这个分叉的状态变成 虽然提交了 但未完成 pending）， 当workflow 完成 这个分叉节点也完成，来实现分叉的分叉的效果
                "C&D" => "E", //C与D全都提交了 就会进入E
                "E" => "X",
                "X" => ["&nY"],//&n开头表示 n 个 Y 并行都提交后完成 &nY，这种时候Y节点下的字段都为json数组存储格式： [{"staffId":"张三","value":"xxxx"},{{"staffId":"李四","value":"yyyy"}}]
                "&nY" => "Z",
                "Z" => ["|nF"], //&n开头表示 n 个 Y 并行其中有一个提交后完成 |nF，未处理的分叉节点不能继续提交，未处理的分叉节点状态变成兄弟节点已经处理完成。
                "|nF" => "W",
                "W" => ["G", "H"],
                "G|H" => "I",//G或H有一个提交 就会进入E
                "I" => "J|END",//表示I可以直接结束工作流，也可以继续到节点J，END是默认完成工作流
                "J" => "K",
                "K" => "I" //实现节点之间循环
            ]
        ];
    }

    public function fieldNodeAttributes()
    {
        return [
            "aText" => [
                "displayName" => "A节点输入文本",
                "nodeAttributes" => [
                    "A+W", "B+R", "C+R", "D+R",
                    "E+R", "F+R", "G+R", "H+R",
                    "I+R", "J+R", "K+R",
                    "X+R", "Y+R", "Z+R","W+R",
                ]
            ],
            "bText" => [
                "displayName" => "B节点输入文本",
                "nodeAttributes" => [
                    "B+W", "C+R", "D+R",
                    "E+R", "F+R", "G+R", "H+R",
                    "I+R", "J+R", "K+R",
                    "X+R", "Y+R", "Z+R","W+R",
                ]
            ],
            "cText" => [
                "displayName" => "C节点输入文本",
                "nodeAttributes" => [
                    "C+W", "D+R",
                    "E+R", "F+R", "G+R", "H+R",
                    "I+R", "J+R", "K+R",
                    "X+R", "Y+R", "Z+R","W+R",
                ]
            ],
            "dText" => [
                "displayName" => "D节点输入文本",
                "nodeAttributes" => [
                    "D+W", "E+R", "F+R", "G+R", "H+R",
                    "I+R", "J+R", "K+R",
                    "X+R", "Y+R", "Z+R","W+R",
                ]
            ],
            "eText" => [
                "displayName" => "E节点输入文本",
                "nodeAttributes" => [
                    "E+W", "F+R", "G+R", "H+R",
                    "I+R", "J+R", "K+R",
                    "X+R", "Y+R", "Z+R","W+R",
                ]
            ],
            "fText" => [
                "displayName" => "F节点输入文本",
                "nodeAttributes" => [
                    "F+W", "G+RW", "H+R",//G也能修改上一个F节点的数据
                    "I+R", "J+R", "K+R",
                    "X+R", "Y+R", "Z+R","W+R",
                ]
            ],
            "gText" => [
                "displayName" => "G节点输入文本",
                "nodeAttributes" => [
                    "F+R", "G+W",
                    "H+R",
                    "I+R", "J+R", "K+R",
                    "X+R", "Y+R", "Z+R","W+R",
                ]
            ],
            "hText" => [
                "displayName" => "H节点输入文本",
                "nodeAttributes" => [
                    "H+W", "I+R", "J+R", "K+R", "X+R", "Y+R", "Z+R","W+R",
                ]
            ],
            "iText" => [
                "displayName" => "i节点输入文本",
                "nodeAttributes" => [
                    "I+W", "J+R", "K+R", "X+R", "Y+R", "Z+R","W+R",
                ]
            ],
            "jText" => [
                "displayName" => "j节点输入文本",
                "nodeAttributes" => [
                    "J+W", "K+R", "X+R", "Y+R", "Z+R","W+R",
                ]
            ],
            "kText" => [
                "displayName" => "k节点输入文本",
                "nodeAttributes" => [
                    "K+W", "X+R", "Y+R", "Z+R","W+R",
                ]
            ],
            "xText" => [
                "displayName" => "X节点输入文本",
                "nodeAttributes" => [
                    "X+W", "Y+R", "Z+R","W+R",
                ]
            ],
            "yText" => [
                "displayName" => "Y节点输入文本",
                "nodeAttributes" => [
                    "Y+W", "Z+R","W+R",
                ]
            ],
            "zText" => [
                "displayName" => "Z节点输入文本",
                "nodeAttributes" => [
                    "Z+W","W+R",
                ]
            ],
            "wText" => [
                "displayName" => "W节点输入文本",
                "nodeAttributes" => [
                    "W+W",
                ]
            ],
        ];
    }

    /**
     * @return string
     */
    public function getAText()
    {
        return $this->aText;
    }

    /**
     * @param string $aText
     * @return TemplateWorkflow
     */
    public function setAText($aText)
    {
        $this->aText = $aText;
        return $this;
    }

    /**
     * @return string
     */
    public function getBText()
    {
        return $this->bText;
    }

    /**
     * @param string $bText
     * @return TemplateWorkflow
     */
    public function setBText($bText)
    {
        $this->bText = $bText;
        return $this;
    }

    /**
     * @return string
     */
    public function getCText()
    {
        return $this->cText;
    }

    /**
     * @param string $cText
     * @return TemplateWorkflow
     */
    public function setCText($cText)
    {
        $this->cText = $cText;
        return $this;
    }

    /**
     * @return string
     */
    public function getDText()
    {
        return $this->dText;
    }

    /**
     * @param string $dText
     * @return TemplateWorkflow
     */
    public function setDText($dText)
    {
        $this->dText = $dText;
        return $this;
    }

    /**
     * @return string
     */
    public function getEText()
    {
        return $this->eText;
    }

    /**
     * @param string $eText
     * @return TemplateWorkflow
     */
    public function setEText($eText)
    {
        $this->eText = $eText;
        return $this;
    }

    /**
     * @return string
     */
    public function getFText()
    {
        return $this->fText;
    }

    /**
     * @param string $fText
     * @return TemplateWorkflow
     */
    public function setFText($fText)
    {
        $this->fText = $fText;
        return $this;
    }

    /**
     * @return string
     */
    public function getGText()
    {
        return $this->gText;
    }

    /**
     * @param string $gText
     * @return TemplateWorkflow
     */
    public function setGText($gText)
    {
        $this->gText = $gText;
        return $this;
    }

    /**
     * @return string
     */
    public function getHText()
    {
        return $this->hText;
    }

    /**
     * @param string $hText
     * @return TemplateWorkflow
     */
    public function setHText($hText)
    {
        $this->hText = $hText;
        return $this;
    }

    /**
     * @return string
     */
    public function getIText()
    {
        return $this->iText;
    }

    /**
     * @param string $iText
     * @return TemplateWorkflow
     */
    public function setIText($iText)
    {
        $this->iText = $iText;
        return $this;
    }

    /**
     * @return string
     */
    public function getJText()
    {
        return $this->jText;
    }

    /**
     * @param string $jText
     * @return TemplateWorkflow
     */
    public function setJText($jText)
    {
        $this->jText = $jText;
        return $this;
    }

    /**
     * @return string
     */
    public function getKText()
    {
        return $this->kText;
    }

    /**
     * @param string $kText
     * @return TemplateWorkflow
     */
    public function setKText($kText)
    {
        $this->kText = $kText;
        return $this;
    }

    /**
     * @return string
     */
    public function getXText()
    {
        return $this->xText;
    }

    /**
     * @param string $xText
     * @return TemplateWorkflow
     */
    public function setXText($xText)
    {
        $this->xText = $xText;
        return $this;
    }

    /**
     * @return string
     */
    public function getYText()
    {
        return $this->yText;
    }

    /**
     * @param string $yText
     * @return TemplateWorkflow
     */
    public function setYText($yText)
    {
        $this->yText = $yText;
        return $this;
    }

    /**
     * @return string
     */
    public function getZText()
    {
        return $this->zText;
    }

    /**
     * @param string $zText
     * @return TemplateWorkflow
     */
    public function setZText($zText)
    {
        $this->zText = $zText;
        return $this;
    }

    /**
     * @return string
     */
    public function getWText()
    {
        return $this->wText;
    }

    /**
     * @param string $wText
     * @return TemplateWorkflow
     */
    public function setWText($wText)
    {
        $this->wText = $wText;
        return $this;
    }


    public function getWorkflowDesc()
    {
        return [
            "displayName" => "例子工作流",
            "description" => "用于开发测试用的工作流例子。"
        ];
    }
}