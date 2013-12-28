<?php

/*
* This file is part of the XabbuhPandaBundle package.
*
* (c) Christian Flothmann <christian.flothmann@xabbuh.de>
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace Xabbuh\PandaBundle\Cloud;

use Xabbuh\PandaClient\Model\Encoding;
use Xabbuh\PandaClient\Model\Notifications;
use Xabbuh\PandaClient\Model\Profile;
use Xabbuh\PandaClient\Model\Video;
use Xabbuh\PandaClient\Transformer\TransformerFactory;
use Xabbuh\PandaClient\ApiInterface;

/**
 * Intuitive PHP interface for the Panda video encoding service API.
 * 
 * This client wraps an Panda API implementation. Every response of an API call
 * is passed to the data transformation layer which transforms the JSON response
 * of the webservice into a matching model object.
 *
 * @author Christian Flothmann <christian.flothmann@xabbuh.de>
 */
class Cloud
{
    /**
     * The wrapped Panda API implementation
     * 
     * @var \Xabbuh\PandaClient\ApiInterface
     */
    private $pandaApi;
    
    /**
     * The factory that creates and manages transformer instances
     * 
     * @var \Xabbuh\PandaBundle\Services\TransformerFactory
     */
    private $transformerFactory;


    /**
     * Constructs the Panda client service.
     *
     * @param \Xabbuh\PandaClient\Api $pandaApi The Panda API implementation being wrapped
     * @param \Xabbuh\PandaBundle\Services\TransformerFactory $transformerFactory The transformer factory
     */
    public function __construct(ApiInterface $pandaApi, TransformerFactory $transformerFactory)
    {
        $this->pandaApi = $pandaApi;
        $this->transformerFactory = $transformerFactory;
    }
    
    /**
     * Returns the Panda API implementation.
     * 
     * @return \Xabbuh\PandaClient\ApiInterface The Panda API implementation
     */
    public function getPandaApi()
    {
        return $this->pandaApi;
    }

    /**
     * Retrieve a collection of videos from the server.
     *
     * @return \Xabbuh\PandaBundle\Model\Video[] Array of Videos
     */
    public function getVideos()
    {
        $response = $this->pandaApi->getVideos();
        $transformer = $this->transformerFactory->get("Video");
        return $transformer->fromJSONCollection($response);
    }

    /**
     * Retrieve a part of all videos for pagination.
     *
     * This method returns an object with the following four properties:
     * <ul>
     * <li>videos: an array of Video model objects</li>
     * <li>page: the current page</li>
     * <li>per_page: number of videos per page (as requested)</li>
     * <li>total: total number of videos</li>
     * </ul>
     *
     * @param int $page The current page
     * @param int $per_page Number of videos per page
     * @return \stdClass The result object
     */
    public function getVideosForPagination($page = 1, $per_page = 100)
    {
        $transformer = $this->transformerFactory->get("Video");
        $response = $this->pandaApi->getVideosForPagination($page, $per_page);
        $json = json_decode($response);
        foreach ($json->videos as $index => $video) {
            $json->videos[$index] = $transformer->fromObject($video);
        }
        return $json;
    }

    /**
     * Fetch data of a video.
     *
     * @param string $videoId The video id
     * @return \Xabbuh\PandaBundle\Model\Video The video
     */
    public function getVideo($videoId)
    {
        $transformer = $this->transformerFactory->get("Video");
        $response = $this->pandaApi->getVideo($videoId);
        return $transformer->fromJSON($response);
    }

    /**
     * Fetch metadata of a video.
     *
     * @param string $videoId The video id
     * @return array Associative array of video metadata
     */
    public function getVideoMetadata($videoId)
    {
        $response = $this->pandaApi->getVideoMetadata($videoId);
        return get_object_vars(json_decode($response));
    }

    /**
     * Delete a video.
     *
     * @param \Xabbuh\PandaBundle\Model\Video $video The video being deleted
     * @return string The server response
     */
    public function deleteVideo(Video $video)
    {
        return $this->pandaApi->deleteVideo($video->getId());
    }
    
    /**
     * Send a request to the Panda encoding service to encode a video file that
     * can be found under a particular url.
     * 
     * @param string $url The url where the encoding service can fetch the video
     * @return \Xabbuh\PandaBundle\Model\Video The created video
     */
    public function encodeVideoByUrl($url)
    {
        $response = $this->pandaApi->encodeVideoByUrl($url);
        $transformer = $this->transformerFactory->get("Video");
        return $transformer->fromJSON($response);
    }
    
    /**
     * Upload a video file to the Panda encoding service.
     * 
     * @param string $localPath The path to the local video file
     * @return \Xabbuh\PandaBundle\Model\Video The created video
     */
    public function encodeVideoFile($localPath)
    {
        $response = $this->pandaApi->encodeVideoFile($localPath);
        $transformer = $this->transformerFactory->get("Video");
        return $transformer->fromJSON($response);
    }

    /**
     * Register an upload session for a specific file.
     *
     * @param string $filename The name of the file to transfer
     * @param string $filesize The size of the file in bytes
     * @param array $profiles Array of profile names for which encodings will
     * be created (by default no encodings will be created)
     * @param bool $useAllProfiles If true create encodings for all profiles
     * (is only taken in account if the list of profile names is null)
     * @return \stdClass An object containing the id of the video after uploading
     * and a URL to which the file needs to be pushed.
     */
    public function registerUpload($filename, $filesize, array $profiles = null, $useAllProfiles = false) {
        return json_decode($this->pandaApi->registerUpload(
            $filename,
            $filesize,
            $profiles,
            $useAllProfiles)
        );
    }
    /**
     * Receive encodings from the server.
     *
     * Filters can be any set of key-value-pairs:
     * <ul>
     *   <li>status: one of 'success', 'fail' or 'processing' to filter by status</li>
     *   <li>profile_id: filter encodings by profile id</li>
     *   <li>profile_name: filter encodings by profile names</li>
     *   <li>video_id: filter by video id
     * </ul>
     *
     * @param array $filter Optional set of filters
     * @return \Xabbuh\PandaBundle\Model\Encoding[] A collection of Encoding objects
     */
    public function getEncodings(array $filter = array())
    {
        $transformer = $this->transformerFactory->get("Encoding");
        $response = $this->pandaApi->getEncodings($filter);
        return $transformer->fromJSONCollection($response);
    }

    /**
     * Receive encodings filtered by status from the server.
     *
     * @see PandaApi::getEncodings()
     * @param string $status Status to filter by (one of 'success', 'fail' or 'processing')
     * @param array $filter Additional optional filters (see
     *     {@link PandaApi::getEncodings() PandaApi::getEncodings()} for a description
     *     of the filters which can be used)
     * @return \Xabbuh\PandaBundle\Model\Encoding[] A collection of Encoding objects
     */
    public function getEncodingsWithStatus($status, array $filter = array())
    {
        $filter["status"] = $status;
        return $this->getEncodings($filter);
    }

    /**
     * Receive encodings filtered by a profile id from the server.
     *
     * @see PandaApi::getEncodings()
     * @param string $profileId Id of the profile to filter by
     * @param array $filter Additional optional filters (see
     *     {@link PandaApi::getEncodings() PandaApi::getEncodings()} for a description
     *     of the filters which can be used)
     * @return \Xabbuh\PandaBundle\Model\Encoding[] A collection of Encoding objects
     */
    public function getEncodingsForProfile($profileId, array $filter = array())
    {
        $filter["profile_id"] = $profileId;
        return $this->getEncodings($filter);
    }

    /**
     * Receive encodings filtered by profile name from the server.
     *
     * @see PandaApi::getEncodings()
     * @param string $profileName Name of the profile to filter by
     * @param array $filter Additional optional filters (see
     *     {@link PandaApi::getEncodings() PandaApi::getEncodings()} for a description
     *     of the filters which can be used)
     * @return \Xabbuh\PandaBundle\Model\Encoding[] A collection of Encoding objects
     */
    public function getEncodingsForProfileByName($profileName, array $filter = array())
    {
        $filter["profile_name"] = $profileName;
        return $this->getEncodings($filter);
    }

    /**
     * Receive encodings filtered by video from the server.
     *
     * @see PandaApi::getEncodings()
     * @param string $videoId Id of the video to filter by
     * @param array $filter Additional optional filters (see
     *     {@link PandaApi::getEncodings() PandaApi::getEncodings()} for a description
     *     of the filters which can be used)
     * @return \Xabbuh\PandaBundle\Model\Encoding[] A collection of Encoding objects
     */
    public function getEncodingsForVideo($videoId, array $filter = array())
    {
        $filter["video_id"] = $videoId;
        return $this->getEncodings($filter);
    }

    /**
     * Get an encoding's data.
     *
     * @param string $encodingId Id of the encoding
     * @return \Xabbuh\PandaBundle\Model\Encoding The encoding
     */
    public function getEncoding($encodingId)
    {
        $transformer = $this->transformerFactory->get("Encoding");
        $response = $this->pandaApi->getEncoding($encodingId);
        return $transformer->fromJSON($response);
    }

    /**
     * Create an encoding based on a profile.
     *
     * @param \Xabbuh\PandaBundle\Model\Video $video The video being encoded
     * @param \Xabbuh\PandaBundle\Model\Profile $profile The profile used to encode the video
     * @return \Xabbuh\PandaBundle\Model\Encoding The new encoding
     */
    public function createEncoding(Video $video, Profile $profile)
    {
        $transformer = $this->transformerFactory->get("Encoding");
        $response = $this->pandaApi->createEncoding(
            $video->getId(),
            $profile->getId()
        );
        return $transformer->fromJSON($response);
    }

    /**
     * Create an encoding based on a profile.
     *
     * @param \Xabbuh\PandaBundle\Model\Video $video The video being encoded
     * @param string $profileId Id of the profile used to encode the video
     * @return \Xabbuh\PandaBundle\Model\Encoding The new encoding
     */
    public function createEncodingWithProfileId(Video $video, $profileId)
    {
        $transformer = $this->transformerFactory->get("Encoding");
        $response = $this->pandaApi->createEncoding(
            $video->getId(),
            $profileId
        );
        return $transformer->fromJSON($response);
    }

    /**
     * Create an encoding for the profile with the given name.
     *
     * @param \Xabbuh\PandaBundle\Model\Video $video The video being encoded
     * @param string $profileName Name of the profile used to encode the video
     * @return \Xabbuh\PandaBundle\Model\Encoding The new encoding
     */
    public function createEncodingWithProfileName(Video $video, $profileName)
    {
        $transformer = $this->transformerFactory->get("Encoding");
        $response = $this->pandaApi->createEncodingWithProfileName(
            $video->getId(),
            $profileName
        );
        return $transformer->fromJSON($response);
    }

    /**
     * Cancel an encoding.
     *
     * @param \Xabbuh\PandaBundle\Model\Encoding $encoding The encoding being canceled
     * @return string The server response
     */
    public function cancelEncoding(Encoding $encoding)
    {
        return $this->pandaApi->cancelEncoding($encoding->getId());
    }

    /**
     * Retry a failed encoding.
     *
     * @param \Xabbuh\PandaBundle\Model\Encoding $encoding The failed encoding
     * @return string The server response
     */
    public function retryEncoding($encoding)
    {
        return $this->pandaApi->retryEncoding($encoding->getId());
    }

    /**
     * Delete an encoding.
     *
     * @param \Xabbuh\PandaBundle\Model\Encoding $encoding The encoding being deleted
     * @return string The server response
     */
    public function deleteEncoding(Encoding $encoding)
    {
        return $this->pandaApi->deleteEncoding($encoding->getId());
    }

    /**
     * Retrieve all profiles.
     *
     * @return \Xabbuh\PandaBundle\Model\Profile[] The list of profiles
     */
    public function getProfiles()
    {
        $transformer = $this->transformerFactory->get("Profile");
        return $transformer->fromJSONCollection($this->pandaApi->getProfiles());
    }

    /**
     * Retrieve information for a profile.
     *
     * @param string $profileId The id of the profile being fetched
     * @return \Xabbuh\PandaBundle\Model\Profile The profile
     */
    public function getProfile($profileId)
    {
        $transformer = $this->transformerFactory->get("Profile");
        return $transformer->fromJSON($this->pandaApi->getProfile($profileId));
    }

    /**
     * Create a profile.
     *
     * @param \Xabbuh\PandaBundle\Model\Profile $profile The new profile's data
     * @return \Xabbuh\PandaBundle\Model\Profile The new profile
     */
    public function addProfile(Profile $profile)
    {
        $transformer = $this->transformerFactory->get("Profile");
        $data = $transformer->toRequestParams($profile)->all();
        $response = $this->pandaApi->addProfile($data);
        return $transformer->fromJSON($response);
    }

    /**
     * Create a profile based on a given preset.
     *
     * @param string $presetName Name of the preset to use
     * @return \Xabbuh\PandaBundle\Model\Profile The new profile
     */
    public function addProfileFromPreset($presetName)
    {
        $transformer = $this->transformerFactory->get("Profile");
        $response = $this->pandaApi->addProfileFromPreset($presetName);
        return $transformer->fromJSON($response);
    }

    /**
     * Change the data of a profile.
     *
     * @param \Xabbuh\PandaBundle\Model\Profile $data The profile's new data
     * @return \Xabbuh\PandaBundle\Model\Profile The modified profile
     */
    public function setProfile(Profile $profile)
    {
        $transformer = $this->transformerFactory->get("Profile");
        $params = $transformer->toRequestParams($profile);
        $response = $this->pandaApi->setProfile(
            $profile->getId(),
            $params->all()
        );
        return $transformer->fromJSON($response);
    }

    /**
     * Delete a profile.
     *
     * @param \Xabbuh\PandaBundle\Model\Profile $profile The profile being removed
     * @return string The server response
     */
    public function deleteProfile(Profile $profile)
    {
        return $this->pandaApi->deleteProfile($profile->getId());
    }

    /**
     * Fetch the cloud's data from the panda server.
     * 
     * @return \Xabbuh\PandaBundle\Model\Cloud The cloud's model data
     */
    public function getCloudData()
    {
        $response = $this->pandaApi->getCloud($this->pandaApi->getRestClient()->getCloudId());
        $transformer = $this->transformerFactory->get("Cloud");
        return $transformer->fromJSON($response);
    }

    /**
     * Change cloud data.
     *
     * @param string $cloudId Id of the Cloud being modified
     * @param array $data The Cloud's new data
     * @return \Xabbuh\PandaBundle\Model\Cloud The Cloud data (changes
     * already reflected)
     */
    public function setCloud($cloudId, array $data)
    {
        $transformer = $this->transformerFactory->get("Cloud");
        $response = $this->pandaApi->setCloud($cloudId, $data);
        return $transformer->fromJSON($response);
    }
    
    /**
     * Retrieve the cloud's notifications configuration.
     * 
     * @return \Xabbuh\PandaBundle\Model\Notifications The notifications
     */
    public function getNotifications()
    {
        $transformer = $this->transformerFactory->get("Notifications");
        return $transformer->fromJSON($this->pandaApi->getNotifications());
    }
    
    /**
     * Change the notifications configuration.
     * 
     * @param \Xabbuh\PandaBundle\Model\Notifications $notifications The new configuration
     * @return \Xabbuh\PandaBundle\Model\Notifications The new configuration
     */
    public function setNotifications(Notifications $notifications)
    {
        $transformer = $this->transformerFactory->get("Notifications");
        $params = $transformer->toRequestParams($notifications);
        $response = $this->pandaApi->setNotifications($params->all());
        return $transformer->fromJSON($response);
    }
}
