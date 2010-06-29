package org.softao.jassandra;

import java.net.URI;
import java.util.ArrayList;
import java.util.HashMap;
import java.util.List;
import java.util.Map;

import com.google.common.collect.Lists;

/**
 * This is used to handle the URI. Currently, it supports:
 * 
 * <pre>
 * thrift://localhost:9160
 * thrift://localhost:9170?alt=localhost:9160
 * thrift://localhost:9170?alt=localhost:9180,localhost:9160
 * </pre>
 * 
 * As stated in the above example, it supports main host and port with optional
 * host and port list.
 */
public class ConnectionUri {
	private static final Map<String, List<String>> parseQuery(String query) {
		Map<String, List<String>> result = new HashMap<String, List<String>>();
		if (query != null && query.length() > 0) {
			for (String pair : query.split("&")) {
				String trimmed = pair.trim();
				if (trimmed.length() == 0) {
					continue;
				}

				String[] parts = pair.split("=");
				String name = parts[0];
				String value = pair.length() > (name.length() + 1)
								? pair.substring(name.length() + 1)
								: "";
				name = name.trim();
				if (name.length() == 0) {
					continue;
				}

				List<String> list = result.get(name);
				if (list == null) {
					list = new ArrayList<String>();
					result.put(name, list);
				}
				list.add(value);
			}
		}
		return result;
	}

	private final String mUriString;
	private final URI mUri;
	private final Map<String, List<String>> mQueryParams;

	/**
	 * @param connectionUrl
	 *            the connection URL to be parsed
	 */
	public ConnectionUri(final String connectionUrl) {
		mUriString = connectionUrl;

		mUri = URI.create(connectionUrl);
		String query = mUri.getQuery();
		mQueryParams = parseQuery(query);
	}

	/**
	 * @return the original string parsed within the constructor.
	 */
	public String getUriString() {
		return mUriString;
	}

	/**
	 * @return the schema of the URI.
	 */
	public String getSchema() {
		return mUri.getScheme();
	}

	/**
	 * @return the host in the URI.
	 */
	public String getHost() {
		return mUri.getHost();
	}

	/**
	 * @return the port in the URI.
	 */
	public int getPort() {
		return mUri.getPort();
	}

	/**
	 * @param name
	 *            the name of the specified parameter to returned.
	 * @return the parameter for specified <code>name</code>, if parameter with
	 *         specified name does not exist, return <code>null</code>, if there
	 *         are multiple parameters available, return the first one.
	 */
	public String getParameter(final String name) {
		if (name == null) {
			throw new NullPointerException("name");
		}

		List<String> list = mQueryParams.get(name);
		if (list == null || list.size() == 0) {
			return null;
		} else {
			return list.get(0);
		}
	}

	/**
	 * @param name
	 *            the name of parameters to be returned
	 * @return the list of the parameters. If the parameter with specified name
	 *         does not exist, return <code>null</code>.
	 */
	public List<String> getParameterList(final String name) {
		if (name == null) {
			throw new NullPointerException("name");
		}

		List<String> list = mQueryParams.get(name);
		if (list == null) {
			return null;
		}
		return Lists.newArrayList(list);
	}
}
