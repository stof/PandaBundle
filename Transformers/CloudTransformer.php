<?php

/*
* This file is part of the XabbuhPandaBundle package.
*
* (c) Christian Flothmann <christian.flothmann@xabbuh.de>
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace Xabbuh\PandaBundle\Transformers;

use Xabbuh\PandaBundle\Model\Cloud;

/**
 * Transform a cloud from and to different data formats.
 *
 * @author Christian Flothmann <christian.flothmann@xabbuh.de>
 */
class CloudTransformer
{
    /**
     * Transform a JSON encoded string into a Cloud model object.
     *
     * @param string $jsonString The JSON encoded string being transformed
     * @return \Xabbuh\PandaBundle\Model\Cloud The generated Cloud
     */
    public function fromJSON($jsonString)
    {
        $json = json_decode($jsonString);

        $cloud = new Cloud();
        $cloud->setId($json->id);
        $cloud->setName($json->name);
        $cloud->setS3VideosBucket($json->s3_videos_bucket);
        $cloud->setS3PrivateAccess($json->s3_private_access);
        $cloud->setUrl($json->url);
        $cloud->setCreatedAt($json->created_at);
        $cloud->setUpdatedAt($json->updated_at);
        return $cloud;
    }
}
