<?php
/**
 * Google App Engine SDK Auto Loader.
 *
 * !!! THIS FILE IS AUTO GENERATED - DO NOT EDIT !!!
 */

namespace google\appengine\runtime;

if (!defined('GOOGLE_APPENGINE_CLASSLOADER')) {
  define('GOOGLE_APPENGINE_CLASSLOADER', true);

  final class ClassLoader {
    private static $classmap = null;
    private static $sdk_root = null;

    public static function loadClass($class_name) {
      if (self::$classmap === null) {
        self::$classmap = [
          'org\bovigo\vfs\vfsstreamwrapper' => 'third_party/vfsstream/vendor/mikey179/vfsStream/src/main/php/org/bovigo/vfs/vfsStreamWrapper.php',
        'org\bovigo\vfs\vfsstreamfile' => 'third_party/vfsstream/vendor/mikey179/vfsStream/src/main/php/org/bovigo/vfs/vfsStreamFile.php',
        'org\bovigo\vfs\vfsstreamexception' => 'third_party/vfsstream/vendor/mikey179/vfsStream/src/main/php/org/bovigo/vfs/vfsStreamException.php',
        'org\bovigo\vfs\vfsstreamdirectory' => 'third_party/vfsstream/vendor/mikey179/vfsStream/src/main/php/org/bovigo/vfs/vfsStreamDirectory.php',
        'org\bovigo\vfs\vfsstreamcontent' => 'third_party/vfsstream/vendor/mikey179/vfsStream/src/main/php/org/bovigo/vfs/vfsStreamContent.php',
        'org\bovigo\vfs\vfsstreamcontaineriterator' => 'third_party/vfsstream/vendor/mikey179/vfsStream/src/main/php/org/bovigo/vfs/vfsStreamContainerIterator.php',
        'org\bovigo\vfs\vfsstreamcontainer' => 'third_party/vfsstream/vendor/mikey179/vfsStream/src/main/php/org/bovigo/vfs/vfsStreamContainer.php',
        'org\bovigo\vfs\vfsstreamabstractcontent' => 'third_party/vfsstream/vendor/mikey179/vfsStream/src/main/php/org/bovigo/vfs/vfsStreamAbstractContent.php',
        'org\bovigo\vfs\vfsstream' => 'third_party/vfsstream/vendor/mikey179/vfsStream/src/main/php/org/bovigo/vfs/vfsStream.php',
        'org\bovigo\vfs\quota' => 'third_party/vfsstream/vendor/mikey179/vfsStream/src/main/php/org/bovigo/vfs/Quota.php',
        'org\bovigo\vfs\visitor\vfsstreamvisitor' => 'third_party/vfsstream/vendor/mikey179/vfsStream/src/main/php/org/bovigo/vfs/visitor/vfsStreamVisitor.php',
        'org\bovigo\vfs\visitor\vfsstreamstructurevisitor' => 'third_party/vfsstream/vendor/mikey179/vfsStream/src/main/php/org/bovigo/vfs/visitor/vfsStreamStructureVisitor.php',
        'org\bovigo\vfs\visitor\vfsstreamprintvisitor' => 'third_party/vfsstream/vendor/mikey179/vfsStream/src/main/php/org/bovigo/vfs/visitor/vfsStreamPrintVisitor.php',
        'org\bovigo\vfs\visitor\vfsstreamabstractvisitor' => 'third_party/vfsstream/vendor/mikey179/vfsStream/src/main/php/org/bovigo/vfs/visitor/vfsStreamAbstractVisitor.php',
        'google\datastore\v1beta3\entityresult\resulttype' => 'google/datastore/v1beta3/query.php',
        'google\datastore\v1beta3\entityresult' => 'google/datastore/v1beta3/query.php',
        'google\datastore\v1beta3\query' => 'google/datastore/v1beta3/query.php',
        'google\datastore\v1beta3\kindexpression' => 'google/datastore/v1beta3/query.php',
        'google\datastore\v1beta3\propertyreference' => 'google/datastore/v1beta3/query.php',
        'google\datastore\v1beta3\projection' => 'google/datastore/v1beta3/query.php',
        'google\datastore\v1beta3\propertyorder\direction' => 'google/datastore/v1beta3/query.php',
        'google\datastore\v1beta3\propertyorder' => 'google/datastore/v1beta3/query.php',
        'google\datastore\v1beta3\filter' => 'google/datastore/v1beta3/query.php',
        'google\datastore\v1beta3\compositefilter\operator' => 'google/datastore/v1beta3/query.php',
        'google\datastore\v1beta3\compositefilter' => 'google/datastore/v1beta3/query.php',
        'google\datastore\v1beta3\propertyfilter\operator' => 'google/datastore/v1beta3/query.php',
        'google\datastore\v1beta3\propertyfilter' => 'google/datastore/v1beta3/query.php',
        'google\datastore\v1beta3\stcontainsfilter' => 'google/datastore/v1beta3/query.php',
        'google\datastore\v1beta3\georegion\circle' => 'google/datastore/v1beta3/query.php',
        'google\datastore\v1beta3\georegion\rectangle' => 'google/datastore/v1beta3/query.php',
        'google\datastore\v1beta3\georegion' => 'google/datastore/v1beta3/query.php',
        'google\datastore\v1beta3\gqlquery\namedbindingsentry' => 'google/datastore/v1beta3/query.php',
        'google\datastore\v1beta3\gqlquery' => 'google/datastore/v1beta3/query.php',
        'google\datastore\v1beta3\gqlqueryparameter' => 'google/datastore/v1beta3/query.php',
        'google\datastore\v1beta3\queryresultbatch\moreresultstype' => 'google/datastore/v1beta3/query.php',
        'google\datastore\v1beta3\queryresultbatch' => 'google/datastore/v1beta3/query.php',
        'google\datastore\v1beta3\partitionid' => 'google/datastore/v1beta3/entity.php',
        'google\datastore\v1beta3\key\pathelement' => 'google/datastore/v1beta3/entity.php',
        'google\datastore\v1beta3\key' => 'google/datastore/v1beta3/entity.php',
        'google\datastore\v1beta3\arrayvalue' => 'google/datastore/v1beta3/entity.php',
        'google\datastore\v1beta3\value' => 'google/datastore/v1beta3/entity.php',
        'google\datastore\v1beta3\entity\propertiesentry' => 'google/datastore/v1beta3/entity.php',
        'google\datastore\v1beta3\entity' => 'google/datastore/v1beta3/entity.php',
        'google\datastore\v1beta3\lookuprequest' => 'google/datastore/v1beta3/datastore.php',
        'google\datastore\v1beta3\lookupresponse' => 'google/datastore/v1beta3/datastore.php',
        'google\datastore\v1beta3\runqueryrequest' => 'google/datastore/v1beta3/datastore.php',
        'google\datastore\v1beta3\runqueryresponse' => 'google/datastore/v1beta3/datastore.php',
        'google\datastore\v1beta3\begintransactionrequest' => 'google/datastore/v1beta3/datastore.php',
        'google\datastore\v1beta3\begintransactionresponse' => 'google/datastore/v1beta3/datastore.php',
        'google\datastore\v1beta3\rollbackrequest' => 'google/datastore/v1beta3/datastore.php',
        'google\datastore\v1beta3\rollbackresponse' => 'google/datastore/v1beta3/datastore.php',
        'google\datastore\v1beta3\commitrequest\mode' => 'google/datastore/v1beta3/datastore.php',
        'google\datastore\v1beta3\commitrequest' => 'google/datastore/v1beta3/datastore.php',
        'google\datastore\v1beta3\commitresponse' => 'google/datastore/v1beta3/datastore.php',
        'google\datastore\v1beta3\allocateidsrequest' => 'google/datastore/v1beta3/datastore.php',
        'google\datastore\v1beta3\allocateidsresponse' => 'google/datastore/v1beta3/datastore.php',
        'google\datastore\v1beta3\mutation' => 'google/datastore/v1beta3/datastore.php',
        'google\datastore\v1beta3\mutationresult' => 'google/datastore/v1beta3/datastore.php',
        'google\datastore\v1beta3\readoptions\readconsistency' => 'google/datastore/v1beta3/datastore.php',
        'google\datastore\v1beta3\readoptions' => 'google/datastore/v1beta3/datastore.php',
        'google\datastore\v1beta3\transactionoptions\readwrite' => 'google/datastore/v1beta3/datastore.php',
        'google\datastore\v1beta3\transactionoptions\readonly' => 'google/datastore/v1beta3/datastore.php',
        'google\datastore\v1beta3\transactionoptions' => 'google/datastore/v1beta3/datastore.php',
        'google\appengine\util\stringutil' => 'google/appengine/util/string_util.php',
        'google\appengine\util\httputil' => 'google/appengine/util/http_util.php',
        'google\appengine\util\arrayutiltest' => 'google/appengine/util/array_util_test.php',
        'google\appengine\util\arrayutil' => 'google/appengine/util/array_util.php',
        'google\appengine\testing\testutils' => 'google/appengine/testing/TestUtils.php',
        'google\appengine\testing\apiproxytestbase' => 'google/appengine/testing/ApiProxyTestBase.php',
        'google\appengine\testing\apiproxymock' => 'google/appengine/testing/ApiProxyMock.php',
        'google\appengine\testing\apicallarguments' => 'google/appengine/testing/ApiCallArguments.php',
        'google\appengine\runtime\vmapiproxy' => 'google/appengine/runtime/VmApiProxy.php',
        'google\appengine\runtime\virtualfilesystem' => 'google/appengine/runtime/VirtualFileSystem.php',
        'google\appengine\runtime\unlinkuploads' => 'google/appengine/runtime/UnlinkUploads.php',
        'google\appengine\runtime\sploverride' => 'google/appengine/runtime/SplOverride.php',
        'google\appengine\runtime\responsetoolargeerror' => 'google/appengine/runtime/ResponseTooLargeError.php',
        'google\appengine\runtime\requesttoolargeerror' => 'google/appengine/runtime/RequestTooLargeError.php',
        'google\appengine\runtime\remoteapiproxy' => 'google/appengine/runtime/RemoteApiProxy.php',
        'google\appengine\runtime\realapiproxy' => 'google/appengine/runtime/RealApiProxy.php',
        'google\appengine\runtime\rpcfailederror' => 'google/appengine/runtime/RPCFailedError.php',
        'google\appengine\runtime\overquotaerror' => 'google/appengine/runtime/OverQuotaError.php',
        'memcached' => 'google/appengine/runtime/Memcached.php',
        'google\appengine\runtime\memcacheutils' => 'google/appengine/runtime/MemcacheUtils.php',
        'memcache' => 'google/appengine/runtime/Memcache.php',
        'google\appengine\runtime\mail' => 'google/appengine/runtime/Mail.php',
        'google\appengine\runtime\glob' => 'google/appengine/runtime/Glob.php',
        'google\appengine\runtime\featurenotenablederror' => 'google/appengine/runtime/FeatureNotEnabledError.php',
        'google\appengine\runtime\error' => 'google/appengine/runtime/Error.php',
        'google\appengine\runtime\directuploadhandler' => 'google/appengine/runtime/DirectUploadHandler.php',
        'google\appengine\runtime\deadlineexceedederror' => 'google/appengine/runtime/DeadlineExceededError.php',
        'google\appengine\runtime\curlliteoptionnotsupportedexception' => 'google/appengine/runtime/CurlLiteOptionNotSupportedException.php',
        'google\appengine\runtime\curllitemethodnotsupportedexception' => 'google/appengine/runtime/CurlLiteMethodNotSupportedException.php',
        'google\appengine\runtime\curllite' => 'google/appengine/runtime/CurlLite.php',
        'google\appengine\runtime\capabilitydisablederror' => 'google/appengine/runtime/CapabilityDisabledError.php',
        'google\appengine\runtime\cancellederror' => 'google/appengine/runtime/CancelledError.php',
        'google\appengine\runtime\callnotfounderror' => 'google/appengine/runtime/CallNotFoundError.php',
        'google\appengine\runtime\argumenterror' => 'google/appengine/runtime/ArgumentError.php',
        'google\appengine\runtime\applicationerror' => 'google/appengine/runtime/ApplicationError.php',
        'google\appengine\runtime\apiproxybase' => 'google/appengine/runtime/ApiProxyBase.php',
        'google\appengine\runtime\apiproxy' => 'google/appengine/runtime/ApiProxy.php',
        'google\net\protocolmessage' => 'google/appengine/runtime/proto/ProtocolMessage.php',
        'google\net\protocolbufferencodeerror' => 'google/appengine/runtime/proto/ProtocolBufferEncodeError.php',
        'google\net\protocolbufferdecodeerror' => 'google/appengine/runtime/proto/ProtocolBufferDecodeError.php',
        'google\net\encoder' => 'google/appengine/runtime/proto/Encoder.php',
        'google\net\decoder' => 'google/appengine/runtime/proto/Decoder.php',
        'google\appengine\ext\session\memcachecontainer' => 'google/appengine/ext/session/MemcacheSessionHandler.php',
        'google\appengine\ext\session\memcachesessionhandler' => 'google/appengine/ext/session/MemcacheSessionHandler.php',
        'google\appengine\ext\remote_api\request' => 'google/appengine/ext/remote_api/remote_api_pb.php',
        'google\appengine\ext\remote_api\applicationerror' => 'google/appengine/ext/remote_api/remote_api_pb.php',
        'google\appengine\ext\remote_api\rpcerror\errorcode' => 'google/appengine/ext/remote_api/remote_api_pb.php',
        'google\appengine\ext\remote_api\rpcerror' => 'google/appengine/ext/remote_api/remote_api_pb.php',
        'google\appengine\ext\remote_api\response' => 'google/appengine/ext/remote_api/remote_api_pb.php',
        'google\appengine\ext\remote_api\transactionrequest\precondition' => 'google/appengine/ext/remote_api/remote_api_pb.php',
        'google\appengine\ext\remote_api\transactionrequest' => 'google/appengine/ext/remote_api/remote_api_pb.php',
        'google\appengine\ext\remote_api\transactionqueryresult' => 'google/appengine/ext/remote_api/remote_api_pb.php',
        'google\appengine\ext\cloud_storage_streams\httpresponse' => 'google/appengine/ext/cloud_storage_streams/HttpResponse.php',
        'google\appengine\ext\cloud_storage_streams\cloudstoragewriteclient' => 'google/appengine/ext/cloud_storage_streams/CloudStorageWriteClient.php',
        'google\appengine\ext\cloud_storage_streams\cloudstorageurlstatclient' => 'google/appengine/ext/cloud_storage_streams/CloudStorageUrlStatClient.php',
        'google\appengine\ext\cloud_storage_streams\cloudstoragestreamwrapper' => 'google/appengine/ext/cloud_storage_streams/CloudStorageStreamWrapper.php',
        'google\appengine\ext\cloud_storage_streams\cloudstoragerenameclient' => 'google/appengine/ext/cloud_storage_streams/CloudStorageRenameClient.php',
        'google\appengine\ext\cloud_storage_streams\cloudstoragereadclient' => 'google/appengine/ext/cloud_storage_streams/CloudStorageReadClient.php',
        'google\appengine\ext\cloud_storage_streams\cloudstoragedirectoryclient' => 'google/appengine/ext/cloud_storage_streams/CloudStorageDirectoryClient.php',
        'google\appengine\ext\cloud_storage_streams\cloudstoragedeleteclient' => 'google/appengine/ext/cloud_storage_streams/CloudStorageDeleteClient.php',
        'google\appengine\ext\cloud_storage_streams\cloudstorageclient' => 'google/appengine/ext/cloud_storage_streams/CloudStorageClient.php',
        'google\appengine\urlfetchserviceerror\errorcode' => 'google/appengine/api/urlfetch_service_pb.php',
        'google\appengine\urlfetchserviceerror' => 'google/appengine/api/urlfetch_service_pb.php',
        'google\appengine\urlfetchrequest\requestmethod' => 'google/appengine/api/urlfetch_service_pb.php',
        'google\appengine\urlfetchrequest\header' => 'google/appengine/api/urlfetch_service_pb.php',
        'google\appengine\urlfetchrequest' => 'google/appengine/api/urlfetch_service_pb.php',
        'google\appengine\urlfetchresponse\header' => 'google/appengine/api/urlfetch_service_pb.php',
        'google\appengine\urlfetchresponse' => 'google/appengine/api/urlfetch_service_pb.php',
        'google\appengine\sourcelocation' => 'google/appengine/api/source_pb.php',
        'google\appengine\base\stringproto' => 'google/appengine/api/api_base_pb.php',
        'google\appengine\base\integer32proto' => 'google/appengine/api/api_base_pb.php',
        'google\appengine\base\integer64proto' => 'google/appengine/api/api_base_pb.php',
        'google\appengine\base\boolproto' => 'google/appengine/api/api_base_pb.php',
        'google\appengine\base\doubleproto' => 'google/appengine/api/api_base_pb.php',
        'google\appengine\base\bytesproto' => 'google/appengine/api/api_base_pb.php',
        'google\appengine\base\voidproto' => 'google/appengine/api/api_base_pb.php',
        'google\appengine\searchserviceerror\errorcode' => 'google/appengine/api/search/search_service_pb.php',
        'google\appengine\searchserviceerror' => 'google/appengine/api/search/search_service_pb.php',
        'google\appengine\requeststatus' => 'google/appengine/api/search/search_service_pb.php',
        'google\appengine\indexspec\consistency' => 'google/appengine/api/search/search_service_pb.php',
        'google\appengine\indexspec\source' => 'google/appengine/api/search/search_service_pb.php',
        'google\appengine\indexspec\mode' => 'google/appengine/api/search/search_service_pb.php',
        'google\appengine\indexspec' => 'google/appengine/api/search/search_service_pb.php',
        'google\appengine\indexmetadata\indexstate' => 'google/appengine/api/search/search_service_pb.php',
        'google\appengine\indexmetadata\storage' => 'google/appengine/api/search/search_service_pb.php',
        'google\appengine\indexmetadata' => 'google/appengine/api/search/search_service_pb.php',
        'google\appengine\indexdocumentparams\freshness' => 'google/appengine/api/search/search_service_pb.php',
        'google\appengine\indexdocumentparams' => 'google/appengine/api/search/search_service_pb.php',
        'google\appengine\indexdocumentrequest' => 'google/appengine/api/search/search_service_pb.php',
        'google\appengine\indexdocumentresponse' => 'google/appengine/api/search/search_service_pb.php',
        'google\appengine\deletedocumentparams' => 'google/appengine/api/search/search_service_pb.php',
        'google\appengine\deletedocumentrequest' => 'google/appengine/api/search/search_service_pb.php',
        'google\appengine\deletedocumentresponse' => 'google/appengine/api/search/search_service_pb.php',
        'google\appengine\listdocumentsparams' => 'google/appengine/api/search/search_service_pb.php',
        'google\appengine\listdocumentsrequest' => 'google/appengine/api/search/search_service_pb.php',
        'google\appengine\listdocumentsresponse' => 'google/appengine/api/search/search_service_pb.php',
        'google\appengine\deleteindexparams' => 'google/appengine/api/search/search_service_pb.php',
        'google\appengine\deleteindexrequest' => 'google/appengine/api/search/search_service_pb.php',
        'google\appengine\deleteindexresponse' => 'google/appengine/api/search/search_service_pb.php',
        'google\appengine\canceldeleteindexparams' => 'google/appengine/api/search/search_service_pb.php',
        'google\appengine\canceldeleteindexrequest' => 'google/appengine/api/search/search_service_pb.php',
        'google\appengine\canceldeleteindexresponse' => 'google/appengine/api/search/search_service_pb.php',
        'google\appengine\listindexesparams' => 'google/appengine/api/search/search_service_pb.php',
        'google\appengine\listindexesrequest' => 'google/appengine/api/search/search_service_pb.php',
        'google\appengine\listindexesresponse' => 'google/appengine/api/search/search_service_pb.php',
        'google\appengine\deleteschemaparams' => 'google/appengine/api/search/search_service_pb.php',
        'google\appengine\deleteschemarequest' => 'google/appengine/api/search/search_service_pb.php',
        'google\appengine\deleteschemaresponse' => 'google/appengine/api/search/search_service_pb.php',
        'google\appengine\sortspec' => 'google/appengine/api/search/search_service_pb.php',
        'google\appengine\scorerspec\scorer' => 'google/appengine/api/search/search_service_pb.php',
        'google\appengine\scorerspec' => 'google/appengine/api/search/search_service_pb.php',
        'google\appengine\fieldspec\expression' => 'google/appengine/api/search/search_service_pb.php',
        'google\appengine\fieldspec' => 'google/appengine/api/search/search_service_pb.php',
        'google\appengine\facetrange' => 'google/appengine/api/search/search_service_pb.php',
        'google\appengine\facetrequestparam' => 'google/appengine/api/search/search_service_pb.php',
        'google\appengine\facetautodetectparam' => 'google/appengine/api/search/search_service_pb.php',
        'google\appengine\facetrequest' => 'google/appengine/api/search/search_service_pb.php',
        'google\appengine\facetrefinement\range' => 'google/appengine/api/search/search_service_pb.php',
        'google\appengine\facetrefinement' => 'google/appengine/api/search/search_service_pb.php',
        'google\appengine\searchparams\cursortype' => 'google/appengine/api/search/search_service_pb.php',
        'google\appengine\searchparams\parsingmode' => 'google/appengine/api/search/search_service_pb.php',
        'google\appengine\searchparams' => 'google/appengine/api/search/search_service_pb.php',
        'google\appengine\searchrequest' => 'google/appengine/api/search/search_service_pb.php',
        'google\appengine\facetresultvalue' => 'google/appengine/api/search/search_service_pb.php',
        'google\appengine\facetresult' => 'google/appengine/api/search/search_service_pb.php',
        'google\appengine\searchresult' => 'google/appengine/api/search/search_service_pb.php',
        'google\appengine\searchresponse' => 'google/appengine/api/search/search_service_pb.php',
        'google\appengine\modulesserviceerror\errorcode' => 'google/appengine/api/modules/modules_service_pb.php',
        'google\appengine\modulesserviceerror' => 'google/appengine/api/modules/modules_service_pb.php',
        'google\appengine\getmodulesrequest' => 'google/appengine/api/modules/modules_service_pb.php',
        'google\appengine\getmodulesresponse' => 'google/appengine/api/modules/modules_service_pb.php',
        'google\appengine\getversionsrequest' => 'google/appengine/api/modules/modules_service_pb.php',
        'google\appengine\getversionsresponse' => 'google/appengine/api/modules/modules_service_pb.php',
        'google\appengine\getdefaultversionrequest' => 'google/appengine/api/modules/modules_service_pb.php',
        'google\appengine\getdefaultversionresponse' => 'google/appengine/api/modules/modules_service_pb.php',
        'google\appengine\getnuminstancesrequest' => 'google/appengine/api/modules/modules_service_pb.php',
        'google\appengine\getnuminstancesresponse' => 'google/appengine/api/modules/modules_service_pb.php',
        'google\appengine\setnuminstancesrequest' => 'google/appengine/api/modules/modules_service_pb.php',
        'google\appengine\setnuminstancesresponse' => 'google/appengine/api/modules/modules_service_pb.php',
        'google\appengine\startmodulerequest' => 'google/appengine/api/modules/modules_service_pb.php',
        'google\appengine\startmoduleresponse' => 'google/appengine/api/modules/modules_service_pb.php',
        'google\appengine\stopmodulerequest' => 'google/appengine/api/modules/modules_service_pb.php',
        'google\appengine\stopmoduleresponse' => 'google/appengine/api/modules/modules_service_pb.php',
        'google\appengine\gethostnamerequest' => 'google/appengine/api/modules/modules_service_pb.php',
        'google\appengine\gethostnameresponse' => 'google/appengine/api/modules/modules_service_pb.php',
        'google\appengine\api\modules\transientmodulesexception' => 'google/appengine/api/modules/TransientModulesException.php',
        'google\appengine\api\modules\modulesservice' => 'google/appengine/api/modules/ModulesService.php',
        'google\appengine\api\modules\modulesexception' => 'google/appengine/api/modules/ModulesException.php',
        'google\appengine\api\modules\invalidmodulestateexception' => 'google/appengine/api/modules/InvalidModuleStateException.php',
        'google\appengine\memcacheserviceerror\errorcode' => 'google/appengine/api/memcache/memcache_service_pb.php',
        'google\appengine\memcacheserviceerror' => 'google/appengine/api/memcache/memcache_service_pb.php',
        'google\appengine\appoverride' => 'google/appengine/api/memcache/memcache_service_pb.php',
        'google\appengine\memcachegetrequest' => 'google/appengine/api/memcache/memcache_service_pb.php',
        'google\appengine\memcachegetresponse\item' => 'google/appengine/api/memcache/memcache_service_pb.php',
        'google\appengine\memcachegetresponse' => 'google/appengine/api/memcache/memcache_service_pb.php',
        'google\appengine\memcachesetrequest\setpolicy' => 'google/appengine/api/memcache/memcache_service_pb.php',
        'google\appengine\memcachesetrequest\item' => 'google/appengine/api/memcache/memcache_service_pb.php',
        'google\appengine\memcachesetrequest' => 'google/appengine/api/memcache/memcache_service_pb.php',
        'google\appengine\memcachesetresponse\setstatuscode' => 'google/appengine/api/memcache/memcache_service_pb.php',
        'google\appengine\memcachesetresponse' => 'google/appengine/api/memcache/memcache_service_pb.php',
        'google\appengine\memcachedeleterequest\item' => 'google/appengine/api/memcache/memcache_service_pb.php',
        'google\appengine\memcachedeleterequest' => 'google/appengine/api/memcache/memcache_service_pb.php',
        'google\appengine\memcachedeleteresponse\deletestatuscode' => 'google/appengine/api/memcache/memcache_service_pb.php',
        'google\appengine\memcachedeleteresponse' => 'google/appengine/api/memcache/memcache_service_pb.php',
        'google\appengine\memcacheincrementrequest\direction' => 'google/appengine/api/memcache/memcache_service_pb.php',
        'google\appengine\memcacheincrementrequest' => 'google/appengine/api/memcache/memcache_service_pb.php',
        'google\appengine\memcacheincrementresponse\incrementstatuscode' => 'google/appengine/api/memcache/memcache_service_pb.php',
        'google\appengine\memcacheincrementresponse' => 'google/appengine/api/memcache/memcache_service_pb.php',
        'google\appengine\memcachebatchincrementrequest' => 'google/appengine/api/memcache/memcache_service_pb.php',
        'google\appengine\memcachebatchincrementresponse' => 'google/appengine/api/memcache/memcache_service_pb.php',
        'google\appengine\memcacheflushrequest' => 'google/appengine/api/memcache/memcache_service_pb.php',
        'google\appengine\memcacheflushresponse' => 'google/appengine/api/memcache/memcache_service_pb.php',
        'google\appengine\memcachestatsrequest' => 'google/appengine/api/memcache/memcache_service_pb.php',
        'google\appengine\mergednamespacestats' => 'google/appengine/api/memcache/memcache_service_pb.php',
        'google\appengine\memcachestatsresponse' => 'google/appengine/api/memcache/memcache_service_pb.php',
        'google\appengine\memcachegrabtailrequest' => 'google/appengine/api/memcache/memcache_service_pb.php',
        'google\appengine\memcachegrabtailresponse\item' => 'google/appengine/api/memcache/memcache_service_pb.php',
        'google\appengine\memcachegrabtailresponse' => 'google/appengine/api/memcache/memcache_service_pb.php',
        'google\appengine\api\cloud_storage\cloudstoragetools' => 'google/appengine/api/cloud_storage/CloudStorageTools.php',
        'google\appengine\api\cloud_storage\cloudstorageexception' => 'google/appengine/api/cloud_storage/CloudStorageException.php',
        'google\appengine\appidentityserviceerror\errorcode' => 'google/appengine/api/app_identity/app_identity_service_pb.php',
        'google\appengine\appidentityserviceerror' => 'google/appengine/api/app_identity/app_identity_service_pb.php',
        'google\appengine\signforapprequest' => 'google/appengine/api/app_identity/app_identity_service_pb.php',
        'google\appengine\signforappresponse' => 'google/appengine/api/app_identity/app_identity_service_pb.php',
        'google\appengine\getpubliccertificateforapprequest' => 'google/appengine/api/app_identity/app_identity_service_pb.php',
        'google\appengine\publiccertificate' => 'google/appengine/api/app_identity/app_identity_service_pb.php',
        'google\appengine\getpubliccertificateforappresponse' => 'google/appengine/api/app_identity/app_identity_service_pb.php',
        'google\appengine\getserviceaccountnamerequest' => 'google/appengine/api/app_identity/app_identity_service_pb.php',
        'google\appengine\getserviceaccountnameresponse' => 'google/appengine/api/app_identity/app_identity_service_pb.php',
        'google\appengine\getaccesstokenrequest' => 'google/appengine/api/app_identity/app_identity_service_pb.php',
        'google\appengine\getaccesstokenresponse' => 'google/appengine/api/app_identity/app_identity_service_pb.php',
        'google\appengine\getdefaultgcsbucketnamerequest' => 'google/appengine/api/app_identity/app_identity_service_pb.php',
        'google\appengine\getdefaultgcsbucketnameresponse' => 'google/appengine/api/app_identity/app_identity_service_pb.php',
        'google\appengine\api\app_identity\publiccertificate' => 'google/appengine/api/app_identity/PublicCertificate.php',
        'google\appengine\api\app_identity\appidentityservice' => 'google/appengine/api/app_identity/AppIdentityService.php',
        'google\appengine\api\app_identity\appidentityexception' => 'google/appengine/api/app_identity/AppIdentityException.php',
        ];
        $base_dir = dirname(__FILE__);
        self::$sdk_root = dirname(dirname(dirname($base_dir))) .
                          DIRECTORY_SEPARATOR;
      }
      $class_name = strtolower($class_name);
      if (array_key_exists($class_name, self::$classmap)) {
        $target_file = self::$classmap[$class_name];
        $full_path = self::$sdk_root . $target_file;
        if (file_exists($full_path)) {
          require $full_path;
        } else {
          require $target_file;
        }
      }
    }
  }

  spl_autoload_register(__NAMESPACE__ . '\ClassLoader::loadClass');

}  // defined ('GOOGLE_APPENGINE_CLASSLOADER')

