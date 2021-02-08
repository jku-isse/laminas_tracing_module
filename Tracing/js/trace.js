import wrapFetch from 'zipkin-instrumentation-fetch';
import { HttpLogger } from 'zipkin-transport-http';
import { BatchRecorder, ExplicitContext, jsonEncoder, Tracer } from 'zipkin';
import StackTrace from 'stacktrace-js';

const config = {
  tracing: {
    endpoint: 'http://127.0.0.1:9411/api/v2/spans',
    localServiceName: 'web-frontend',
  },
  stackTrace: {
    include: true,
    internalFileLocation: 'webpack-internal:///./src',
    apiFunctions: ['getRequest', 'postRequest', 'deleteRequest', 'putRequest', 'patchRequest'],
  },
};

const logger = new HttpLogger({ endpoint: config.tracing.endpoint, jsonEncoder: jsonEncoder.JSON_V2 });
const recorder = new BatchRecorder({ logger });
const ctxImpl = new ExplicitContext();

export default function instrumented(fetch, url, options) {
  return (config.stackTrace.include ? loadStackTrace() : Promise.resolve(undefined)).then((stack) => {
    const defaultTags = {
      page: typeof document !== 'undefined' ? document.location.pathname : 'SSR',
    };
    if (stack) {
      defaultTags.stack = JSON.stringify(stack);
    }
    const tracer = new Tracer({
      ctxImpl,
      recorder,
      localServiceName: config.tracing.localServiceName,
      defaultTags,
    });

    const urlObj = new URL(url);
    const remoteServiceName = urlObj.host + '/' + urlObj.pathname.split('/')[1];
    const zipkinFetch = wrapFetch(global.fetch, { tracer: tracer, remoteServiceName });

    return zipkinFetch(url, options);
  });
}

function loadStackTrace() {
  return StackTrace.get()
    .then(processStackTrace)
    .catch((err) => {
      console.error('Error while reading stacktrace: ', err.message);
    });
}

function processStackTrace(stackFrames) {
  const interestingStackFrames = stackFrames.filter((sf) => {
    const functionName = sf.getFunctionName();

    const isInternalFile = sf.getFileName().startsWith(config.stackTrace.internalFileLocation);
    const isNamedFunction =
      typeof functionName === 'undefined' ? false : !functionName.includes('$') && !functionName.includes('_');

    return isInternalFile && isNamedFunction;
  });

  const apiIndex = interestingStackFrames.findIndex((sf) =>
    config.stackTrace.apiFunctions.includes(sf.getFunctionName())
  );
  if (apiIndex) {
    interestingStackFrames.splice(0, apiIndex);
  }

  return interestingStackFrames.map((sf) => ({
    file: sf.getFileName().replace(config.stackTrace.internalFileLocation + '/', ''),
    function: sf.getFunctionName(),
  }));
}
